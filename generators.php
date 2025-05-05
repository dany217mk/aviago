<?php

$host = 'localhost';
$port = '5432';
$db = 'aviago';
$user = 'postgres';
$password = 'dan$mos2';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Подключение к БД успешно";
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}


function seedRoles(PDO $pdo) {
    $roles = [
        ['name' => 'Администратор сайта', 'access_level' => 10],
        ['name' => 'Администратор авиакомпниии', 'access_level' => 3],
        ['name' => 'Планирование авиакомпании', 'access_level' => 5],
        ['name' => 'Пассажир', 'access_level' => 1],
    ];

    $stmt = $pdo->prepare("INSERT INTO role (name, access_level) VALUES (:name, :access_level)");

    foreach ($roles as $role) {
        $stmt->execute([
            ':name' => $role['name'],
            ':access_level' => $role['access_level']
        ]);
    }

    echo "\nРоли успешно добавлены\n";
}


function seedPositions(PDO $pdo) {
    $positions = [
        'Администратор авиакомпании',
        'Планировщик рейсов',
        'Командир воздушного судна (КВС)',
        'Второй пилот',
        'Инструктор пилотов',
        'Старший бортпроводник',
        'Бортпроводник',
    ];

    $stmt = $pdo->prepare("INSERT INTO position (name) VALUES (:name)");

    foreach ($positions as $name) {
        $stmt->execute([':name' => $name]);
    }

    echo "\nДолжности сотрудников успешно добавлены\n";
}


function seedUsers(PDO $pdo) {
    $firstNames = ['Александр', 'Дмитрий', 'Максим', 'Сергей', 'Андрей', 'Алексей', 'Иван', 'Олег', 'Павел', 'Евгений'];
    $lastNames = ['Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Попов', 'Смирнов', 'Васильев', 'Новиков', 'Морозов', 'Волков'];
    $patronymics = ['Александрович', 'Дмитриевич', 'Сергеевич', 'Владимирович', 'Игоревич', 'Николаевич', 'Павлович'];

    $positions = getPositionIds($pdo);
    $airlines = getAirlineIds($pdo);   

    $stmtUser = $pdo->prepare("
        INSERT INTO user_account (role_id, email, password, surname, name, patronymic) 
        VALUES (:role_id, :email, :password, :surname, :name, :patronymic)
    ");

    $stmtPassenger = $pdo->prepare("
        INSERT INTO user_passenger (user_id, passport_series, passport_number, date_of_birth, nationality, gender) 
        VALUES (:user_id, :passport_series, :passport_number, :date_of_birth, :nationality, :gender)
    ");

    $stmtWorker = $pdo->prepare("
        INSERT INTO worker_details (user_id, position_id, airline_id, hired_at)
        VALUES (:user_id, :position_id, :airline_id, :hired_at)
    ");

    for ($i = 1; $i <= 10; $i++) {
        $name = $firstNames[array_rand($firstNames)];
        $surname = $lastNames[array_rand($lastNames)];
        $patronymic = $patronymics[array_rand($patronymics)];
        $email = strtolower(translit($name)) . ".$i@" . "example.com";
        $password = md5("12345");
        $role_id = 2;

        $stmtUser->execute([
            ':role_id' => $role_id,
            ':email' => $email,
            ':password' => $password,
            ':surname' => $surname,
            ':name' => $name,
            ':patronymic' => $patronymic
        ]);

        $userId = $pdo->lastInsertId();

        if ($role_id == 3) {
            $passportSeries = rand(1000, 9999);
            $passportNumber = rand(100000, 999999);
            $birthDate = date('Y-m-d', strtotime('-' . rand(18, 60) . ' years -' . rand(0, 365) . ' days'));
            $nationality = 'Россия';
            $gender = rand(0, 1) ? 'male' : 'female';

            $stmtPassenger->execute([
                ':user_id' => $userId,
                ':passport_series' => $passportSeries,
                ':passport_number' => $passportNumber,
                ':date_of_birth' => $birthDate,
                ':nationality' => $nationality,
                ':gender' => $gender
            ]);
        } else {
            $position_id = $positions[array_rand($positions)];
            $airline_id = $airlines[array_rand($airlines)];
            $hiredAt = date('Y-m-d', strtotime('-' . rand(1, 10) . ' years -' . rand(0, 365) . ' days'));

            $stmtWorker->execute([
                ':user_id' => $userId,
                ':position_id' => $position_id,
                ':airline_id' => $airline_id,
                ':hired_at' => $hiredAt
            ]);
        }
    }

    echo "30 пользователей сгенерированы и распределены между пассажирами и сотрудниками.\n";
}
function translit($text) {
    $converter = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i',
        'й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t',
        'у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y',
        'ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];
    return strtr(mb_strtolower($text), $converter);
}

function getPositionIds(PDO $pdo) {
    $stmt = $pdo->query("SELECT id FROM position");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getAirlineIds(PDO $pdo) {
    $stmt = $pdo->query("SELECT id FROM airline");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function seedAirlines(PDO $pdo) {
    $airlines = [
        ['name' => 'Аэрофлот', 'icao' => 'AFL', 'iata' => 'SU', 'country' => 'Россия'],
        ['name' => 'S7 Airlines', 'icao' => 'SBI', 'iata' => 'S7', 'country' => 'Россия'],
        ['name' => 'UTair', 'icao' => 'TYU', 'iata' => 'UT', 'country' => 'Россия'],
        ['name' => 'Россия', 'icao' => 'SDM', 'iata' => 'FV', 'country' => 'Россия'],
        ['name' => 'Победа', 'icao' => 'PLD', 'iata' => 'DP', 'country' => 'Россия'],
        ['name' => 'Уральские авиалинии', 'icao' => 'SVR', 'iata' => 'U6', 'country' => 'Россия'],
        ['name' => 'Азимут', 'icao' => 'AZM', 'iata' => 'ZF', 'country' => 'Россия'],
        ['name' => 'Алроса', 'icao' => 'DRZ', 'iata' => '7R', 'country' => 'Россия'],
        ['name' => 'КрасАвиа', 'icao' => 'KCA', 'iata' => 'KC', 'country' => 'Россия'],
        ['name' => 'Камчатские авиалинии', 'icao' => 'KML', 'iata' => 'KB', 'country' => 'Россия']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO airline (airport_id, user_id, name, icao, iata, country, logo)
        VALUES (:airport_id, :user_id, :name, :icao, :iata, :country, :logo)
    ");

    foreach ($airlines as $airline) {
        $airport_id = rand(1, 6);
        $user_id = rand(1, 10);  
        $logo = 'test.png';
        $stmt->execute([
            ':airport_id' => $airport_id,
            ':user_id' => $user_id,
            ':name' => $airline['name'],
            ':icao' => $airline['icao'],
            ':iata' => $airline['iata'],
            ':country' => $airline['country'],
            ':logo' => $logo
        ]);
    }

    echo "10 авиакомпаний сгенерированы.\n";
}

function seedAirports(PDO $pdo) {
    $airports = [
        ['name' => 'Красноярск Шереметьево', 'city' => 'Красноярск', 'country' => 'Россия', 'icao' => 'UKKK', 'iata' => 'KJA'],
        ['name' => 'Владивосток', 'city' => 'Владивосток', 'country' => 'Россия', 'icao' => 'UHWW', 'iata' => 'VVO'],
        ['name' => 'Сочи', 'city' => 'Сочи', 'country' => 'Россия', 'icao' => 'URSS', 'iata' => 'AER'],
        ['name' => 'Мурманск', 'city' => 'Мурманск', 'country' => 'Россия', 'icao' => 'ULMM', 'iata' => 'MMK']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO airport (name, city, country, icao, iata)
        VALUES (:name, :city, :country, :icao, :iata)
    ");

    foreach ($airports as $airport) {
        $stmt->execute([
            ':name' => $airport['name'],
            ':city' => $airport['city'],
            ':country' => $airport['country'],
            ':icao' => $airport['icao'],
            ':iata' => $airport['iata']
        ]);
    }

    echo "10 международных аэропортов России сгенерированы.\n";
}

function seedCharterRequests(PDO $pdo) {
    $cities = [
        'Москва', 'Санкт-Петербург', 'Екатеринбург', 'Новосибирск', 
        'Владивосток', 'Казань', 'Сочи', 'Ростов-на-Дону', 
        'Нижний Новгород', 'Краснодар'
    ];

    $statuses = ['pending', 'approved', 'rejected'];

    $stmtRequest = $pdo->prepare("
        INSERT INTO charter_request (user_id, airline_id, status, departure_city, arrival_city, passenger_count, departure_date, additional_info, allow_public_sales)
        VALUES (:user_id, :airline_id, :status, :departure_city, :arrival_city, :passenger_count, :departure_date, :additional_info, :allow_public_sales)
    ");
    
    $stmtContact = $pdo->prepare("
        INSERT INTO charter_contact (request_id, name, phone, email)
        VALUES (:request_id, :name, :phone, :email)
    ");

    for ($i = 0; $i < 10; $i++) {
        $departureCity = $cities[array_rand($cities)];
        $arrivalCity = $cities[array_rand($cities)];
        $status = $statuses[array_rand($statuses)];
        $passengerCount = rand(1, 100);
        $departureDate = date('Y-m-d', strtotime("+".rand(1, 30)." days"));
        $additionalInfo = 'Дополнительная информация о рейсе ' . rand(1, 1000);
        $allowPublicSales = rand(0, 1);

        $userId = (rand(0, 1) === 0) ? null : rand(1, 30); 


        $stmtRequest->execute([
            ':user_id' => $userId,
            ':airline_id' => rand(1, 10),
            ':status' => $status,
            ':departure_city' => $departureCity,
            ':arrival_city' => $arrivalCity,
            ':passenger_count' => $passengerCount,
            ':departure_date' => $departureDate,
            ':additional_info' => $additionalInfo,
            ':allow_public_sales' => $allowPublicSales
        ]);
        
        $requestId = $pdo->lastInsertId();

        if ($userId === null) {
            $name = 'Контактное лицо ' . rand(1, 100);
            $phone = '+7' . rand(1000000000, 9999999999);
            $email = 'contact' . rand(1, 100) . '@example.com';

            $stmtContact->execute([
                ':request_id' => $requestId,
                ':name' => $name,
                ':phone' => $phone,
                ':email' => $email
            ]);
        }
    }

    echo "10 записей charter_request и charter_contact сгенерированы.\n";
}




function seedAirplanesAndAirlines(PDO $pdo) {
    $airplanes = [
        ['name' => 'Боинг 777', 'icao' => 'B777', 'iata' => 'B77', 'capacity' => '396', 'max_range_km' => 15000, 'info' => 'Дальний рейс, широкий фюзеляж'],
        ['name' => 'Боинг 737', 'icao' => 'B737', 'iata' => 'B73', 'capacity' => '200', 'max_range_km' => 6000, 'info' => 'Средний рейс, экономичный'],
        ['name' => 'Боинг 747', 'icao' => 'B747', 'iata' => 'B74', 'capacity' => '524', 'max_range_km' => 13000, 'info' => 'Широкофюзеляжный, дальний рейс'],
        ['name' => 'Аэрбас A320', 'icao' => 'A320', 'iata' => 'A32', 'capacity' => '180', 'max_range_km' => 6100, 'info' => 'Универсальный самолет для средних рейсов'],
        ['name' => 'Аэрбас A321', 'icao' => 'A321', 'iata' => 'A32', 'capacity' => '220', 'max_range_km' => 6500, 'info' => 'Модификация A320 с удлиненным фюзеляжем'],
        ['name' => 'Аэрбас A319', 'icao' => 'A319', 'iata' => 'A31', 'capacity' => '140', 'max_range_km' => 6700, 'info' => 'Компактный самолет для коротких и средних рейсов'],
        ['name' => 'Аэрбас A350', 'icao' => 'A350', 'iata' => 'A35', 'capacity' => '369', 'max_range_km' => 15000, 'info' => 'Современный дальнемагистральный самолет'],
        ['name' => 'Аэрбас A330', 'icao' => 'A330', 'iata' => 'A33', 'capacity' => '277', 'max_range_km' => 12000, 'info' => 'Международный широкофюзеляжный самолет'],
        ['name' => 'Сухой Суперджет', 'icao' => 'SSJ', 'iata' => 'SJ', 'capacity' => '87', 'max_range_km' => 4500, 'info' => 'Региональный российский самолет']
    ];

    $stmtAirplane = $pdo->prepare("
        INSERT INTO airplane (name, icao, iata, capacity, max_range_km, info)
        VALUES (:name, :icao, :iata, :capacity, :max_range_km, :info)
    ");

    $stmtAirplaneAirline = $pdo->prepare("
        INSERT INTO airplane_airline (airplane_id, airline_id, registration)
        VALUES (:airplane_id, :airline_id, :registration)
    ");

    $airlineIds = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    foreach ($airplanes as $airplane) {

        $stmtAirplane->execute([
            ':name' => $airplane['name'],
            ':icao' => $airplane['icao'],
            ':iata' => $airplane['iata'],
            ':capacity' => $airplane['capacity'],
            ':max_range_km' => $airplane['max_range_km'],
            ':info' => $airplane['info']
        ]);


        $airplaneId = $pdo->lastInsertId();

        $airlineId = $airlineIds[array_rand($airlineIds)];


        $registration = strtoupper(substr(uniqid(), -6));

        $stmtAirplaneAirline->execute([
            ':airplane_id' => $airplaneId,
            ':airline_id' => $airlineId,
            ':registration' => $registration
        ]);
    }

    echo "Самолеты и их связи с авиакомпаниями были успешно добавлены.\n";
}

function seedFlightStatus(PDO $pdo) {
    $flightStatuses = [
        'Scheduled',    
        'Check-in',           
        'Boarding Started',   
        'Last Call',           
        'Boarding Completed',  
        'Departed',            
        'In Flight',          
        'Arrived',             
        'Diverted',            
        'Cancelled',           
        'Delayed',            
        'Completed'            
    ];


    $stmt = $pdo->prepare("INSERT INTO flight_status (status_name) VALUES (:status_name)");

    
    foreach ($flightStatuses as $status) {
        $stmt->execute([':status_name' => $status]);
    }

    echo "Статусы рейсов успешно добавлены.\n";
}


function seedFlights(PDO $pdo) {
   
    $airportsStmt = $pdo->query("SELECT id FROM airport");
    $airports = $airportsStmt->fetchAll(PDO::FETCH_ASSOC);

    
    $airplanesStmt = $pdo->query("SELECT id FROM airplane");
    $airplanes = $airplanesStmt->fetchAll(PDO::FETCH_ASSOC);

    
    $flightStatusesStmt = $pdo->query("SELECT id FROM flight_status");
    $flightStatuses = $flightStatusesStmt->fetchAll(PDO::FETCH_ASSOC);


    function randomDateTime($startDate, $endDate) {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);
        return date('Y-m-d H:i:s', $randomTimestamp);
    }


    $stmt = $pdo->prepare("
        INSERT INTO flight (dep_airport_id, arr_airport_id, flight_status_id, airplane_id, dep_time, arr_time, distance)
        VALUES (:dep_airport_id, :arr_airport_id, :flight_status_id, :airplane_id, :dep_time, :arr_time, :distance)
    ");

    for ($i = 0; $i < 10; $i++) {
        $depAirport = $airports[array_rand($airports)];
        $arrAirport = $airports[array_rand($airports)];
        while ($depAirport['id'] === $arrAirport['id']) {
            $arrAirport = $airports[array_rand($airports)];
        }

        $airplane = $airplanes[array_rand($airplanes)];


        $flightStatus = $flightStatuses[array_rand($flightStatuses)];

        $depTime = randomDateTime('2025-04-22 00:00:00', '2025-04-22 23:59:59');
        $arrTime = randomDateTime(date('Y-m-d H:i:s', strtotime($depTime)), date('Y-m-d H:i:s', strtotime($depTime) + 3600 * 10));

        $distance = rand(300, 15000);

        $stmt->execute([
            ':dep_airport_id' => $depAirport['id'],
            ':arr_airport_id' => $arrAirport['id'],
            ':flight_status_id' => $flightStatus['id'],
            ':airplane_id' => $airplane['id'],
            ':dep_time' => $depTime,
            ':arr_time' => $arrTime,
            ':distance' => $distance
        ]);
    }

    echo "Рейсы успешно добавлены.\n";
}



function seedBookings(PDO $pdo) {
    $flightsStmt = $pdo->query("SELECT id FROM flight");
    $flights = $flightsStmt->fetchAll(PDO::FETCH_ASSOC);


    $bookingStatuses = ['reserved', 'confirmed', 'checked-in', 'canceled'];


    $stmt = $pdo->prepare("
        INSERT INTO booking (flight_id, status, created_at, modified_at)
        VALUES (:flight_id, :status, :created_at, :modified_at)
    ");

    for ($i = 0; $i < 20; $i++) {
        $flight = $flights[array_rand($flights)];

        $status = $bookingStatuses[array_rand($bookingStatuses)];

        $createdAt = date('Y-m-d H:i:s');
        $modifiedAt = date('Y-m-d H:i:s');

        $stmt->execute([
            ':flight_id' => $flight['id'],
            ':status' => $status,
            ':created_at' => $createdAt,
            ':modified_at' => $modifiedAt
        ]);
    }

    echo "✅ Бронирования успешно добавлены.\n";
}

    function seedCrew(PDO $pdo) {
        $flightsStmt = $pdo->query("SELECT id FROM flight");
        $flights = $flightsStmt->fetchAll(PDO::FETCH_ASSOC);

        $workersStmt = $pdo->query("SELECT id FROM worker_details");
        $workers = $workersStmt->fetchAll(PDO::FETCH_ASSOC);
    
 
        $stmt = $pdo->prepare("
            INSERT INTO crew (flight_id, worker_id)
            VALUES (:flight_id, :worker_id)
        ");
    
      
        for ($i = 0; $i < 30; $i++) {
           
            $flight = $flights[array_rand($flights)];
    
           
            $worker = $workers[array_rand($workers)];
    
            
            $stmt->execute([
                ':flight_id' => $flight['id'],
                ':worker_id' => $worker['id']
            ]);
        }
    
        echo "✅ Экипажи успешно добавлены.\n";
    }


    function seedSeats(PDO $pdo) {
        $airplanesStmt = $pdo->query("SELECT id FROM airplane");
        $airplanes = $airplanesStmt->fetchAll(PDO::FETCH_ASSOC);
    
        $seatTypes = ['economy', 'business', 'first'];
    
        $stmt = $pdo->prepare("
            INSERT INTO seat (airplane_id, number, type, is_emergency_exit)
            VALUES (:airplane_id, :number, :type, :is_emergency_exit)
        ");
    
        foreach ($airplanes as $airplane) {
            for ($i = 1; $i <= 50; $i++) {
                $type = $seatTypes[array_rand($seatTypes)];
                $isEmergencyExit = (rand(1, 10) <= 2); 
    
                $letter = chr(rand(65, 70)); 
                $number = rand(1, 30);
                $seatNumber = $letter . $number;
    
                $stmt->execute([
                    ':airplane_id' => $airplane['id'],
                    ':number' => $seatNumber,
                    ':type' => $type,
                    ':is_emergency_exit' => $isEmergencyExit ? 'true' : 'false' 
                ]);
            }
        }
    
        echo "✅ Места успешно добавлены.\n";
    }


function seedBookingPassengers(PDO $pdo, int $count = 50): void {
    $bookingIds = $pdo->query("SELECT id FROM booking")->fetchAll(PDO::FETCH_COLUMN);
    $seatIds = $pdo->query("SELECT id FROM seat")->fetchAll(PDO::FETCH_COLUMN);
    $userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 22, 28, 29, 31, 37, 39];
    $usedUsers = [];

    $names = ['Иван', 'Петр', 'Сергей', 'Дмитрий', 'Алексей', 'Михаил', 'Антон', 'Виктор'];
    $surnames = ['Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Смирнов', 'Орлов'];
    $patronymics = ['Иванович', 'Петрович', 'Сергеевич', 'Алексеевич', 'Михайлович'];

    $stmtPassenger = $pdo->prepare("
        INSERT INTO passenger_details (name, surname, patronymic, passport_series, passport_number)
        VALUES (?, ?, ?, ?, ?)
        RETURNING id
    ");

    $stmtBookingPassenger = $pdo->prepare("
        INSERT INTO booking_passenger (booking_id, user_id, seat_id)
        VALUES (?, ?, ?)
    ");

    foreach (range(1, $count) as $_) {
        $bookingId = $bookingIds[array_rand($bookingIds)];
        $seatId = $seatIds[array_rand($seatIds)];

        $useUser = rand(0, 1) && count($userIds) > 0;

        if ($useUser) {
            $userId = array_splice($userIds, array_rand($userIds), 1)[0];
            $stmtBookingPassenger->execute([$bookingId, $userId, $seatId]);
        } else {
            $name = $names[array_rand($names)];
            $surname = $surnames[array_rand($surnames)];
            $patronymic = $patronymics[array_rand($patronymics)];
            $passport_series = strval(rand(1000, 9999));
            $passport_number = strval(rand(100000, 999999));

            $stmtPassenger->execute([$name, $surname, $patronymic, $passport_series, $passport_number]);
            $passengerId = $pdo->lastInsertId();

            $pdo->exec("INSERT INTO booking_passenger (booking_id, seat_id) VALUES ($bookingId, $seatId)");
        }
    }

    echo "Генерация booking_passenger и passenger_details завершена.\n";
}


seedBookingPassengers($pdo);




?>