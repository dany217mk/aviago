--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

-- Started on 2025-06-23 09:45:27

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 278 (class 1255 OID 16976)
-- Name: add_airline_and_update_user(character varying, character varying, bigint, character varying, character varying, bigint, text); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.add_airline_and_update_user(IN p_name character varying, IN p_country character varying, IN p_airport_id bigint, IN p_icao character varying, IN p_iata character varying, IN p_user_id bigint, IN p_logo text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO airline (name, country, airport_id, icao, iata, user_id, logo)
    VALUES (p_name, p_country, p_airport_id, p_icao, p_iata, p_user_id, p_logo);

    UPDATE user_account SET role_id = 1 WHERE id = p_user_id;
END;
$$;


ALTER PROCEDURE public.add_airline_and_update_user(IN p_name character varying, IN p_country character varying, IN p_airport_id bigint, IN p_icao character varying, IN p_iata character varying, IN p_user_id bigint, IN p_logo text) OWNER TO postgres;

--
-- TOC entry 277 (class 1255 OID 16975)
-- Name: add_airline_to_charter(bigint, bigint); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.add_airline_to_charter(IN p_airline_id bigint, IN p_charter_id bigint)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE charter_request
    SET airline_id = p_airline_id
    WHERE id = p_charter_id;
END;
$$;


ALTER PROCEDURE public.add_airline_to_charter(IN p_airline_id bigint, IN p_charter_id bigint) OWNER TO postgres;

--
-- TOC entry 260 (class 1255 OID 16811)
-- Name: check_airplane_availability(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.check_airplane_availability() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    conflict_count INT;
BEGIN
    -- Проверяем, есть ли другие рейсы с тем же самолетом и пересечением времени ±1 час
    SELECT COUNT(*) INTO conflict_count
    FROM flight
    WHERE airplane_airline_id = NEW.airplane_airline_id
      AND id <> COALESCE(NEW.id, 0) -- исключаем текущий рейс при обновлении
      AND (
          NEW.dep_time - INTERVAL '1 hour' < arr_time
          AND NEW.arr_time + INTERVAL '1 hour' > dep_time
      );

    IF conflict_count > 0 THEN
        RAISE EXCEPTION 'Самолет занят на другом рейсе в выбранное время с учётом подготовки (+-1 час)';
    END IF;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.check_airplane_availability() OWNER TO postgres;

--
-- TOC entry 261 (class 1255 OID 16815)
-- Name: check_crew_availability(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.check_crew_availability() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    conflicting_flight RECORD;
BEGIN
    -- Получаем время вылета и прилёта текущего рейса
    SELECT dep_time, arr_time INTO STRICT conflicting_flight
    FROM flight WHERE id = NEW.flight_id;

    -- Проверяем, есть ли другой рейс с пересечением по времени для этого worker_id
    IF EXISTS (
        SELECT 1
        FROM crew c
        INNER JOIN flight f ON c.flight_id = f.id
        WHERE c.worker_id = NEW.worker_id
          AND c.flight_id <> NEW.flight_id
          AND f.dep_time <= conflicting_flight.arr_time + interval '30 minutes'
          AND f.arr_time >= conflicting_flight.dep_time - interval '30 minutes'
    ) THEN
        RAISE EXCEPTION 'Этот член экипажа занят на другом рейсе в выбранное время (+-30 минут)';
    END IF;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.check_crew_availability() OWNER TO postgres;

--
-- TOC entry 279 (class 1255 OID 16977)
-- Name: deactivate_worker(bigint); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.deactivate_worker(IN p_worker_id bigint)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE worker_details SET is_active = false WHERE id = p_worker_id;
END;
$$;


ALTER PROCEDURE public.deactivate_worker(IN p_worker_id bigint) OWNER TO postgres;

--
-- TOC entry 257 (class 1255 OID 16973)
-- Name: delete_crew_member(bigint); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.delete_crew_member(IN p_crew_id bigint)
    LANGUAGE plpgsql
    AS $$
BEGIN
    DELETE FROM crew WHERE id = p_crew_id;
END;
$$;


ALTER PROCEDURE public.delete_crew_member(IN p_crew_id bigint) OWNER TO postgres;

--
-- TOC entry 256 (class 1255 OID 16978)
-- Name: edit_worker_account_and_details(character varying, character varying, character varying, date, bigint, text, character varying, bigint, bigint); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.edit_worker_account_and_details(IN p_name character varying, IN p_surname character varying, IN p_patronymic character varying, IN p_hired_at date, IN p_role_id bigint, IN p_position_details text, IN p_email character varying, IN p_user_id bigint, IN p_worker_id bigint)
    LANGUAGE plpgsql
    AS $$
BEGIN

    UPDATE user_account
    SET name = p_name,
        surname = p_surname,
        patronymic = p_patronymic,
        role_id = p_role_id,
        email = p_email
    WHERE id = p_user_id;


    UPDATE worker_details
    SET hired_at = p_hired_at,
        position_details = p_position_details
    WHERE id = p_worker_id;
END;
$$;


ALTER PROCEDURE public.edit_worker_account_and_details(IN p_name character varying, IN p_surname character varying, IN p_patronymic character varying, IN p_hired_at date, IN p_role_id bigint, IN p_position_details text, IN p_email character varying, IN p_user_id bigint, IN p_worker_id bigint) OWNER TO postgres;

--
-- TOC entry 254 (class 1255 OID 16796)
-- Name: generate_booking_number(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.generate_booking_number() RETURNS character varying
    LANGUAGE plpgsql
    AS $$
DECLARE
    letters TEXT := 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    prefix TEXT := '';
    number TEXT;
BEGIN
    FOR i IN 1..3 LOOP
        prefix := prefix || substr(letters, (trunc(random() * length(letters) + 1))::int, 1);
    END LOOP;

    number := lpad(trunc(random() * 1e8)::TEXT, 8, '0');

    RETURN prefix || '-' || number;
END;
$$;


ALTER FUNCTION public.generate_booking_number() OWNER TO postgres;

--
-- TOC entry 253 (class 1255 OID 16781)
-- Name: generate_request_code(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.generate_request_code() RETURNS character varying
    LANGUAGE plpgsql
    AS $$
DECLARE
    letters TEXT := 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    new_code TEXT;
    code_exists BOOLEAN;
BEGIN
    LOOP
        new_code := 
            SUBSTR(letters, 1 + (RANDOM() * 26)::INT, 1) ||
            SUBSTR(letters, 1 + (RANDOM() * 26)::INT, 1) ||
            SUBSTR(letters, 1 + (RANDOM() * 26)::INT, 1) ||
            '-' ||
            LPAD((RANDOM() * 99999)::INT::TEXT, 5, '0');
        
        SELECT EXISTS(SELECT 1 FROM charter_request WHERE request_code = new_code) 
        INTO code_exists;
        
        EXIT WHEN NOT code_exists; 
    END LOOP;
    
    RETURN new_code;
END;
$$;


ALTER FUNCTION public.generate_request_code() OWNER TO postgres;

--
-- TOC entry 258 (class 1255 OID 16809)
-- Name: generate_unique_flight_code(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.generate_unique_flight_code() RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
    code TEXT;
    exists_count INTEGER;
BEGIN
    LOOP
        -- Генерируем 8 случайных символов (a-zA-Z0-9)
        code := substring(md5(random()::text) from 1 for 8);

        -- Проверяем есть ли уже такой код в таблице flight
        SELECT COUNT(*) INTO exists_count FROM flight WHERE flight_code = code;

        IF exists_count = 0 THEN
            RETURN code;
        END IF;
        -- Если есть, повторяем цикл
    END LOOP;
END;
$$;


ALTER FUNCTION public.generate_unique_flight_code() OWNER TO postgres;

--
-- TOC entry 259 (class 1255 OID 16810)
-- Name: generate_unique_flight_number(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.generate_unique_flight_number(iata_code text) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
    number TEXT;
    num_part TEXT;
    exists_count INTEGER;
BEGIN
    LOOP
        -- Генерируем 6 случайных цифр
        num_part := lpad((floor(random() * 1000000))::text, 6, '0');
        number := upper(iata_code) || num_part;

        SELECT COUNT(*) INTO exists_count FROM flight WHERE flight_number = number;

        IF exists_count = 0 THEN
            RETURN number;
        END IF;
        -- Если номер уже есть, повторяем
    END LOOP;
END;
$$;


ALTER FUNCTION public.generate_unique_flight_number(iata_code text) OWNER TO postgres;

--
-- TOC entry 276 (class 1255 OID 16974)
-- Name: get_passenger_charter_requests(bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_passenger_charter_requests(p_user_id bigint) RETURNS TABLE(request_id bigint, request_code character varying, passenger_count integer, status character varying, allow_public_sales boolean, comment text, departure_date date, departure_airport character varying, arrival_airport character varying, airline_name character varying, airline_creator_email character varying, flight_id bigint, flight_number character varying, flight_code character varying, dep_time timestamp without time zone, arr_time timestamp without time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        cr.id AS request_id,
        cr.request_code,
        cr.passenger_count,
        cr.status,
        cr.allow_public_sales,
        cr.comment,
        cr.departure_date,
        dep.name AS departure_airport,
        arr.name AS arrival_airport,
        airline.name AS airline_name,
        creator.email AS airline_creator_email,
        f.id AS flight_id,
        f.flight_number,
        f.flight_code,
        f.dep_time,
        f.arr_time
    FROM charter_request cr
    LEFT JOIN airport dep ON cr.departure_airport_id = dep.id
    LEFT JOIN airport arr ON cr.arrival_airport_id = arr.id
    LEFT JOIN airline ON cr.airline_id = airline.id
    LEFT JOIN user_account creator ON airline.user_id = creator.id
    LEFT JOIN flight f ON f.charter_request_id = cr.id
    WHERE cr.user_id = p_user_id
    ORDER BY cr.departure_date ASC;
END;
$$;


ALTER FUNCTION public.get_passenger_charter_requests(p_user_id bigint) OWNER TO postgres;

--
-- TOC entry 262 (class 1255 OID 16817)
-- Name: prevent_duplicate_crew_member(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.prevent_duplicate_crew_member() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM crew 
        WHERE flight_id = NEW.flight_id AND worker_id = NEW.worker_id
    ) THEN
        RAISE EXCEPTION 'Этот сотрудник уже назначен на этот рейс';
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.prevent_duplicate_crew_member() OWNER TO postgres;

--
-- TOC entry 255 (class 1255 OID 16823)
-- Name: trg_approve_charter_request_on_flight_insert(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.trg_approve_charter_request_on_flight_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE charter_request
    SET status = 'approved'
    WHERE id = NEW.charter_request_id;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.trg_approve_charter_request_on_flight_insert() OWNER TO postgres;

--
-- TOC entry 275 (class 1255 OID 16821)
-- Name: trg_check_airplane_capacity(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.trg_check_airplane_capacity() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_flight_id BIGINT;
    v_airplane_id BIGINT;
    v_capacity BIGINT;
    v_occupied_seats BIGINT;
BEGIN
    -- Получаем flight_id из booking
    SELECT b.flight_id INTO v_flight_id
    FROM booking b
    WHERE b.id = NEW.booking_id;

    -- Получаем airplane_id из flight через airplane_airline
    SELECT f.airplane_airline_id INTO v_airplane_id
    FROM flight f
    WHERE f.id = v_flight_id;

    -- Получаем вместимость самолёта
    SELECT a.capacity INTO v_capacity
    FROM airplane_airline aa
    JOIN airplane a ON aa.airplane_id = a.id
    WHERE aa.id = v_airplane_id;

    -- Считаем сколько мест уже занято на рейсе
    SELECT COUNT(*)
    INTO v_occupied_seats
    FROM booking_passenger bp
    JOIN booking b2 ON bp.booking_id = b2.id
    WHERE b2.flight_id = v_flight_id;

    -- Для обновления учитываем, что может обновляться существующая запись
    IF TG_OP = 'UPDATE' THEN
        -- Если seat_id не изменился, то не считаем текущее место дважды
        IF OLD.seat_id = NEW.seat_id THEN
            v_occupied_seats := v_occupied_seats; -- ничего не меняется
        ELSE
            v_occupied_seats := v_occupied_seats + 1;
        END IF;
    ELSE
        -- INSERT
        v_occupied_seats := v_occupied_seats + 1;
    END IF;

    -- Проверяем вместимость
    IF v_occupied_seats > v_capacity THEN
        RAISE EXCEPTION 'Превышена вместимость самолёта на рейсе (максимум %).', v_capacity;
    END IF;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.trg_check_airplane_capacity() OWNER TO postgres;

--
-- TOC entry 274 (class 1255 OID 16819)
-- Name: trg_check_unique_seat(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.trg_check_unique_seat() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_flight_id BIGINT;
BEGIN
    -- Получаем flight_id через связь booking_passenger -> booking -> flight_id
    SELECT b.flight_id INTO v_flight_id
    FROM booking b
    WHERE b.id = NEW.booking_id;

    -- Проверяем, не занят ли уже seat_id для этого flight_id другим пассажиром
    IF EXISTS (
        SELECT 1
        FROM booking_passenger bp
        JOIN booking b2 ON bp.booking_id = b2.id
        WHERE b2.flight_id = v_flight_id
          AND bp.seat_id = NEW.seat_id
          AND bp.id <> COALESCE(NEW.id, 0) -- исключаем текущую запись при обновлении
    ) THEN
        RAISE EXCEPTION 'Место уже занято для этого рейса';
    END IF;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.trg_check_unique_seat() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 228 (class 1259 OID 16464)
-- Name: airline; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.airline (
    id bigint NOT NULL,
    airport_id bigint,
    user_id bigint,
    name character varying(255) NOT NULL,
    icao character varying(10) NOT NULL,
    iata character varying(10) NOT NULL,
    country character varying(100) NOT NULL,
    logo character varying(255)
);


ALTER TABLE public.airline OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 16463)
-- Name: airline_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.airline ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.airline_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 232 (class 1259 OID 16511)
-- Name: airplane; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.airplane (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    icao character varying(10) NOT NULL,
    iata character varying(10) NOT NULL,
    max_range_km integer,
    info text,
    capacity bigint DEFAULT 100 NOT NULL,
    row_counts integer,
    seats_per_row integer,
    CONSTRAINT airplane_max_range_km_check CHECK ((max_range_km > 0))
);


ALTER TABLE public.airplane OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 16526)
-- Name: airplane_airline; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.airplane_airline (
    id bigint NOT NULL,
    airplane_id bigint NOT NULL,
    airline_id bigint NOT NULL,
    registration character varying(20) NOT NULL
);


ALTER TABLE public.airplane_airline OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 16525)
-- Name: airplane_airline_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.airplane_airline ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.airplane_airline_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 231 (class 1259 OID 16510)
-- Name: airplane_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.airplane ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.airplane_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 226 (class 1259 OID 16452)
-- Name: airport; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.airport (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    city character varying(100) NOT NULL,
    country character varying(100) NOT NULL,
    icao character varying(10) NOT NULL,
    iata character varying(10) NOT NULL
);


ALTER TABLE public.airport OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 16451)
-- Name: airport_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.airport ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.airport_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 244 (class 1259 OID 16604)
-- Name: booking; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booking (
    id bigint NOT NULL,
    flight_id bigint NOT NULL,
    status character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    modified_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    booking_number character varying(12),
    charter_request_id bigint,
    passenger_email character varying,
    CONSTRAINT booking_status_check CHECK (((status)::text = ANY ((ARRAY['reserved'::character varying, 'confirmed'::character varying, 'checked-in'::character varying, 'canceled'::character varying])::text[])))
);


ALTER TABLE public.booking OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 16603)
-- Name: booking_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.booking ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.booking_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 246 (class 1259 OID 16618)
-- Name: booking_passenger; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booking_passenger (
    id bigint NOT NULL,
    booking_id bigint,
    seat_id bigint,
    passenger_id bigint
);


ALTER TABLE public.booking_passenger OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 16617)
-- Name: booking_passenger_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.booking_passenger ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.booking_passenger_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 250 (class 1259 OID 16660)
-- Name: charter_contact; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.charter_contact (
    id bigint NOT NULL,
    request_id bigint,
    contact_fio character varying(100) NOT NULL,
    email character varying(255),
    additional_info text,
    organization_name character varying
);


ALTER TABLE public.charter_contact OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 16659)
-- Name: charter_contact_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.charter_contact ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.charter_contact_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 248 (class 1259 OID 16639)
-- Name: charter_request; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.charter_request (
    id bigint NOT NULL,
    user_id bigint,
    airline_id bigint,
    status character varying(20),
    passenger_count integer,
    departure_date date NOT NULL,
    allow_public_sales boolean DEFAULT true,
    departure_airport_id bigint NOT NULL,
    arrival_airport_id bigint NOT NULL,
    request_code character varying NOT NULL,
    comment text DEFAULT '-'::text,
    is_archived boolean DEFAULT false NOT NULL,
    CONSTRAINT charter_request_passenger_count_check CHECK ((passenger_count > 0)),
    CONSTRAINT charter_request_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.charter_request OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 16638)
-- Name: charter_request_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.charter_request ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.charter_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 252 (class 1259 OID 16734)
-- Name: connect; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.connect (
    id bigint NOT NULL,
    user_id bigint,
    token character(32) NOT NULL,
    "time" timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.connect OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 16733)
-- Name: connects_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.connects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.connects_id_seq OWNER TO postgres;

--
-- TOC entry 5160 (class 0 OID 0)
-- Dependencies: 251
-- Name: connects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.connects_id_seq OWNED BY public.connect.id;


--
-- TOC entry 242 (class 1259 OID 16588)
-- Name: crew; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.crew (
    id bigint NOT NULL,
    flight_id bigint,
    worker_id bigint,
    description character varying
);


ALTER TABLE public.crew OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 16587)
-- Name: crew_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.crew ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.crew_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 240 (class 1259 OID 16561)
-- Name: flight; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.flight (
    id bigint NOT NULL,
    dep_airport_id bigint,
    arr_airport_id bigint,
    flight_status_id bigint,
    airplane_airline_id bigint,
    dep_time timestamp without time zone NOT NULL,
    arr_time timestamp without time zone NOT NULL,
    flight_number character varying(10) NOT NULL,
    allow_public_sales boolean DEFAULT true NOT NULL,
    flight_code character varying(8),
    charter_request_id bigint NOT NULL,
    charter_seats_number integer DEFAULT 0,
    worker_id bigint NOT NULL,
    is_archived boolean DEFAULT false NOT NULL
);


ALTER TABLE public.flight OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 16560)
-- Name: flight_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.flight ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.flight_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 238 (class 1259 OID 16555)
-- Name: flight_status; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.flight_status (
    id bigint NOT NULL,
    status_name character varying(50) NOT NULL
);


ALTER TABLE public.flight_status OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 16554)
-- Name: flight_status_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.flight_status ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.flight_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 224 (class 1259 OID 16436)
-- Name: passenger_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.passenger_details (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    surname character varying(100) NOT NULL,
    patronymic character varying(100),
    passport_series_number character varying(11) NOT NULL
);


ALTER TABLE public.passenger_details OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16435)
-- Name: passenger_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.passenger_details ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.passenger_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 218 (class 1259 OID 16396)
-- Name: role; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    access_level integer,
    CONSTRAINT role_access_level_check CHECK ((access_level >= 0))
);


ALTER TABLE public.role OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 16395)
-- Name: role_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.role ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.role_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 236 (class 1259 OID 16542)
-- Name: seat; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.seat (
    id bigint NOT NULL,
    airplane_id bigint,
    number character varying(10) NOT NULL,
    type character varying(20),
    is_emergency_exit boolean DEFAULT false,
    CONSTRAINT seat_type_check CHECK (((type)::text = ANY ((ARRAY['economy'::character varying, 'business'::character varying, 'first'::character varying])::text[])))
);


ALTER TABLE public.seat OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 16541)
-- Name: seat_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.seat ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.seat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 220 (class 1259 OID 16403)
-- Name: user_account; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_account (
    id bigint NOT NULL,
    role_id bigint DEFAULT 3,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    surname character varying(100) NOT NULL,
    name character varying(100) NOT NULL,
    patronymic character varying(100),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.user_account OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16402)
-- Name: user_account_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.user_account ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.user_account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 222 (class 1259 OID 16420)
-- Name: user_passenger; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_passenger (
    id bigint NOT NULL,
    user_id bigint,
    passport_series_number character varying(11) NOT NULL,
    date_of_birth date NOT NULL,
    gender character varying(10),
    CONSTRAINT user_passenger_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying])::text[])))
);


ALTER TABLE public.user_passenger OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16419)
-- Name: user_passenger_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.user_passenger ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.user_passenger_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 230 (class 1259 OID 16488)
-- Name: worker_details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker_details (
    id bigint NOT NULL,
    user_id bigint,
    airline_id bigint,
    hired_at date NOT NULL,
    position_details character varying,
    is_password_changed boolean DEFAULT false,
    is_active boolean DEFAULT true
);


ALTER TABLE public.worker_details OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 16487)
-- Name: worker_details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.worker_details ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.worker_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 4858 (class 2604 OID 16737)
-- Name: connect id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.connect ALTER COLUMN id SET DEFAULT nextval('public.connects_id_seq'::regclass);


--
-- TOC entry 5130 (class 0 OID 16464)
-- Dependencies: 228
-- Data for Name: airline; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.airline (id, airport_id, user_id, name, icao, iata, country, logo) FROM stdin;
2	4	2	Россия	SDM	FV	RU	9200b8bd776eb6c782c8082b80ae8c1d.jpg
1	1	1	Аэрофлот	AFL	SU	RU	e8f193793ab67b7c838cf701ea5e76c3.png
3	3	19	Победа	PBD	DP	RU	0bf854a3cbf56e73a2f8fd74191e2c87.png
4	5	20	Nordwind Airlines	NWS	N4	RU	0ee8fae0e69411e6e6099f7fe133da07.png
5	2	21	S7 Airlines	SBI	S7	RU	c2625a20d14e8f55a20a1cca92ef1ed5.png
\.


--
-- TOC entry 5134 (class 0 OID 16511)
-- Dependencies: 232
-- Data for Name: airplane; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.airplane (id, name, icao, iata, max_range_km, info, capacity, row_counts, seats_per_row) FROM stdin;
1	Airbus A319	A319	319	6500	Single-aisle narrow-body aircraft	138	23	6
2	Airbus A320	A320	320	6100	Single-aisle narrow-body aircraft	150	25	6
3	Airbus A321	A321	321	7400	Single-aisle narrow-body aircraft	186	31	6
4	Airbus A330	A330	330	13400	Wide-body twin-engine aircraft	279	31	9
5	Sukhoi Superjet 100	SSJ100	SU9	4600	Regional jet aircraft	100	20	5
6	Boeing 737-800	B738	73H	5420	Single-aisle narrow-body aircraft	162	27	6
7	Boeing 777	B777	77W	15600	Wide-body long-range twin-engine aircraft	360	40	9
8	Airbus A350	A350	35K	15000	Wide-body long-range twin-engine aircraft	342	38	9
\.


--
-- TOC entry 5136 (class 0 OID 16526)
-- Dependencies: 234
-- Data for Name: airplane_airline; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.airplane_airline (id, airplane_id, airline_id, registration) FROM stdin;
1	1	2	RA-73100
2	1	2	RA-73101
3	1	2	RA-73102
4	1	2	RA-73103
5	1	2	RA-73104
6	1	2	RA-73105
7	1	2	RA-73106
8	2	2	RA-73107
10	1	2	RA-73108
11	2	2	RA-73109
12	6	2	RA-73110
13	6	2	RA-73111
14	6	2	RA-73112
15	5	2	RA-73113
16	5	2	RA-73114
17	5	2	RA-73115
18	5	2	RA-73116
19	5	2	RA-73117
20	7	2	RA-73118
21	6	3	RA-73200
22	3	1	RA-73201
23	4	4	RA-73850
24	2	5	RA-73450
\.


--
-- TOC entry 5128 (class 0 OID 16452)
-- Dependencies: 226
-- Data for Name: airport; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.airport (id, name, city, country, icao, iata) FROM stdin;
1	Международный аэропорт Шереметьево	Москва	Россия	UUEE	SVO
2	Международный аэропорт Домодедово	Москва	Россия	UUDD	DME
3	Международный аэропорт Внуково	Москва	Россия	UUWW	VKO
4	Международный аэропорт Пулково	Санкт-Петербург	Россия	ULLI	LED
5	Международный аэропорт Казань	Казань	Россия	UWKD	KZN
6	Международный аэропорт Толмачёво	Новосибирск	Россия	UNNT	OVB
7	Международный аэропорт Кольцово	Екатеринбург	Россия	USSS	SVX
8	Международный аэропорт Сочи	Сочи	Россия	URSS	AER
9	Международный аэропорт Курумоч	Самара	Россия	UWWW	KUF
10	Международный аэропорт Ростов-на-Дону	Ростов-на-Дону	Россия	URRR	ROV
11	Международный аэропорт Новый	Хабаровск	Россия	UHHH	KHV
12	Международный аэропорт Владивосток	Владивосток	Россия	UHWW	VVO
13	Международный аэропорт Минеральные Воды	Минеральные Воды	Россия	URMM	MRV
14	Международный аэропорт Стригино	Нижний Новгород	Россия	UWGG	GOJ
15	Международный аэропорт Храброво	Калининград	Россия	UMKK	KGD
16	Международный аэропорт Уфа	Уфа	Россия	UWUU	UFA
17	Международный аэропорт Спиченково	Новокузнецк	Россия	UNWW	NOZ
18	Международный аэропорт Баландино	Челябинск	Россия	USCC	CEK
19	Международный аэропорт Талаги	Архангельск	Россия	ULAA	ARH
20	Международный аэропорт Кемерово	Кемерово	Россия	UNEE	KEJ
\.


--
-- TOC entry 5146 (class 0 OID 16604)
-- Dependencies: 244
-- Data for Name: booking; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.booking (id, flight_id, status, created_at, modified_at, booking_number, charter_request_id, passenger_email) FROM stdin;
1	1	checked-in	2025-06-23 08:54:19.53405	2025-06-23 08:54:19.53405	fjL-94769654	\N	titov@yandex.ru
\.


--
-- TOC entry 5148 (class 0 OID 16618)
-- Dependencies: 246
-- Data for Name: booking_passenger; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.booking_passenger (id, booking_id, seat_id, passenger_id) FROM stdin;
2	1	37	2
8	1	38	8
5	1	43	5
3	1	39	3
4	1	40	4
9	1	41	9
7	1	42	7
1	1	44	1
6	1	45	6
\.


--
-- TOC entry 5152 (class 0 OID 16660)
-- Dependencies: 250
-- Data for Name: charter_contact; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.charter_contact (id, request_id, contact_fio, email, additional_info, organization_name) FROM stdin;
\.


--
-- TOC entry 5150 (class 0 OID 16639)
-- Dependencies: 248
-- Data for Name: charter_request; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.charter_request (id, user_id, airline_id, status, passenger_count, departure_date, allow_public_sales, departure_airport_id, arrival_airport_id, request_code, comment, is_archived) FROM stdin;
1	2	2	approved	120	2025-06-24	t	4	8	UUW-63890	-	f
2	19	3	approved	100	2025-06-25	t	4	8	YXU-37400	-	f
3	1	1	approved	180	2025-06-26	t	4	8	ICW-70571	-	f
4	20	4	approved	200	2025-06-24	t	4	8	RXE-75760	-	f
5	21	5	approved	120	2025-06-24	t	4	8	YXI-05431	-	f
\.


--
-- TOC entry 5154 (class 0 OID 16734)
-- Dependencies: 252
-- Data for Name: connect; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.connect (id, user_id, token, "time") FROM stdin;
7	21	tkxEChLBqEKHur906IBf7vQQgTSq1cfR	2025-06-23 09:41:06
\.


--
-- TOC entry 5144 (class 0 OID 16588)
-- Dependencies: 242
-- Data for Name: crew; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.crew (id, flight_id, worker_id, description) FROM stdin;
2	1	15	Бортпроводник 1
3	1	16	Бортпроводник 2
5	1	9	Safety пилот
6	1	11	Второй пилот (стажер)
7	1	7	Командир воздушного судна (инструктор)
8	1	13	Старший бортпроводник
9	1	14	Бортпроводник 3
\.


--
-- TOC entry 5142 (class 0 OID 16561)
-- Dependencies: 240
-- Data for Name: flight; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.flight (id, dep_airport_id, arr_airport_id, flight_status_id, airplane_airline_id, dep_time, arr_time, flight_number, allow_public_sales, flight_code, charter_request_id, charter_seats_number, worker_id, is_archived) FROM stdin;
1	4	8	2	1	2025-06-24 08:40:00	2025-06-24 13:00:00	FV896343	t	dd181a12	1	120	2	f
2	4	8	1	21	2025-06-25 10:00:00	2025-06-25 14:00:00	DP825641	t	265a3a7d	2	100	19	f
3	4	8	1	22	2025-06-26 12:00:00	2025-06-26 16:00:00	SU101720	t	b28886d3	3	180	1	f
4	4	8	1	23	2025-06-24 16:00:00	2025-06-24 20:00:00	N4531597	t	ed07cca9	4	200	20	f
5	4	8	1	24	2025-06-24 12:00:00	2025-06-24 16:00:00	S7112595	t	c918f243	5	120	21	f
\.


--
-- TOC entry 5140 (class 0 OID 16555)
-- Dependencies: 238
-- Data for Name: flight_status; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.flight_status (id, status_name) FROM stdin;
1	Scheduled
2	Check-in
3	Boarding Started
4	Last Call
5	Boarding Completed
6	Departed
7	In Flight
8	Arrived
9	Diverted
10	Cancelled
11	Delayed
12	Completed
\.


--
-- TOC entry 5126 (class 0 OID 16436)
-- Dependencies: 224
-- Data for Name: passenger_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.passenger_details (id, name, surname, patronymic, passport_series_number) FROM stdin;
1	Марк	Титов	Евгеньевич	1231243214
2	Лев	Беляев	Тимофеевич	5252366332
3	Денис	Данилов	Давидович	6262343243
4	Даниил	Еремин	Маратович	5645664767
5	Тимофей	Губанов	Борисович	7868678768
6	Алексей	Шмелев	Борисович	6456546546
7	Милана	Соловьева	Арсентьевна	4635435435
8	Мирослава	Виноградова	Ярославовна	6547657656
9	Анна	Сергеева	Александровна	5646546546
\.


--
-- TOC entry 5120 (class 0 OID 16396)
-- Dependencies: 218
-- Data for Name: role; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role (id, name, access_level) FROM stdin;
1	Airline Admin	10
2	Flight Planner	8
3	Passenger	5
4	WebSite Admin	12
5	Pilot	6
6	Flight Attendant	6
\.


--
-- TOC entry 5138 (class 0 OID 16542)
-- Dependencies: 236
-- Data for Name: seat; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.seat (id, airplane_id, number, type, is_emergency_exit) FROM stdin;
1	1	1A	first	f
2	1	1B	first	f
3	1	1C	first	f
4	1	1D	first	f
5	1	1E	first	f
6	1	1F	first	f
7	1	2A	first	f
8	1	2B	first	f
9	1	2C	first	f
10	1	2D	first	f
11	1	2E	first	f
12	1	2F	first	f
13	1	3A	business	f
14	1	3B	business	f
15	1	3C	business	f
16	1	3D	business	f
17	1	3E	business	f
18	1	3F	business	f
19	1	4A	business	f
20	1	4B	business	f
21	1	4C	business	f
22	1	4D	business	f
23	1	4E	business	f
24	1	4F	business	f
25	1	5A	business	f
26	1	5B	business	f
27	1	5C	business	f
28	1	5D	business	f
29	1	5E	business	f
30	1	5F	business	f
31	1	6A	economy	f
32	1	6B	economy	f
33	1	6C	economy	f
34	1	6D	economy	f
35	1	6E	economy	f
36	1	6F	economy	f
37	1	7A	economy	f
38	1	7B	economy	f
39	1	7C	economy	f
40	1	7D	economy	f
41	1	7E	economy	f
42	1	7F	economy	f
43	1	8A	economy	f
44	1	8B	economy	f
45	1	8C	economy	f
46	1	8D	economy	f
47	1	8E	economy	f
48	1	8F	economy	f
49	1	9A	economy	f
50	1	9B	economy	f
51	1	9C	economy	f
52	1	9D	economy	f
53	1	9E	economy	f
54	1	9F	economy	f
55	1	10A	economy	f
56	1	10B	economy	f
57	1	10C	economy	f
58	1	10D	economy	f
59	1	10E	economy	f
60	1	10F	economy	f
61	1	11A	economy	f
62	1	11B	economy	f
63	1	11C	economy	f
64	1	11D	economy	f
65	1	11E	economy	f
66	1	11F	economy	f
67	1	12A	economy	t
68	1	12B	economy	t
69	1	12C	economy	t
70	1	12D	economy	t
71	1	12E	economy	t
72	1	12F	economy	t
73	1	13A	economy	f
74	1	13B	economy	f
75	1	13C	economy	f
76	1	13D	economy	f
77	1	13E	economy	f
78	1	13F	economy	f
79	1	14A	economy	f
80	1	14B	economy	f
81	1	14C	economy	f
82	1	14D	economy	f
83	1	14E	economy	f
84	1	14F	economy	f
85	1	15A	economy	f
86	1	15B	economy	f
87	1	15C	economy	f
88	1	15D	economy	f
89	1	15E	economy	f
90	1	15F	economy	f
91	1	16A	economy	f
92	1	16B	economy	f
93	1	16C	economy	f
94	1	16D	economy	f
95	1	16E	economy	f
96	1	16F	economy	f
97	1	17A	economy	f
98	1	17B	economy	f
99	1	17C	economy	f
100	1	17D	economy	f
101	1	17E	economy	f
102	1	17F	economy	f
103	1	18A	economy	f
104	1	18B	economy	f
105	1	18C	economy	f
106	1	18D	economy	f
107	1	18E	economy	f
108	1	18F	economy	f
109	1	19A	economy	f
110	1	19B	economy	f
111	1	19C	economy	f
112	1	19D	economy	f
113	1	19E	economy	f
114	1	19F	economy	f
115	1	20A	economy	f
116	1	20B	economy	f
117	1	20C	economy	f
118	1	20D	economy	f
119	1	20E	economy	f
120	1	20F	economy	f
121	1	21A	economy	f
122	1	21B	economy	f
123	1	21C	economy	f
124	1	21D	economy	f
125	1	21E	economy	f
126	1	21F	economy	f
127	1	22A	economy	f
128	1	22B	economy	f
129	1	22C	economy	f
130	1	22D	economy	f
131	1	22E	economy	f
132	1	22F	economy	f
133	1	23A	economy	f
134	1	23B	economy	f
135	1	23C	economy	f
136	1	23D	economy	f
137	1	23E	economy	f
138	1	23F	economy	f
139	2	1A	first	f
140	2	1B	first	f
141	2	1C	first	f
142	2	1D	first	f
143	2	1E	first	f
144	2	1F	first	f
145	2	2A	first	f
146	2	2B	first	f
147	2	2C	first	f
148	2	2D	first	f
149	2	2E	first	f
150	2	2F	first	f
151	2	3A	first	f
152	2	3B	first	f
153	2	3C	first	f
154	2	3D	first	f
155	2	3E	first	f
156	2	3F	first	f
157	2	4A	business	f
158	2	4B	business	f
159	2	4C	business	f
160	2	4D	business	f
161	2	4E	business	f
162	2	4F	business	f
163	2	5A	business	f
164	2	5B	business	f
165	2	5C	business	f
166	2	5D	business	f
167	2	5E	business	f
168	2	5F	business	f
169	2	6A	business	f
170	2	6B	business	f
171	2	6C	business	f
172	2	6D	business	f
173	2	6E	business	f
174	2	6F	business	f
175	2	7A	economy	f
176	2	7B	economy	f
177	2	7C	economy	f
178	2	7D	economy	f
179	2	7E	economy	f
180	2	7F	economy	f
181	2	8A	economy	f
182	2	8B	economy	f
183	2	8C	economy	f
184	2	8D	economy	f
185	2	8E	economy	f
186	2	8F	economy	f
187	2	9A	economy	f
188	2	9B	economy	f
189	2	9C	economy	f
190	2	9D	economy	f
191	2	9E	economy	f
192	2	9F	economy	f
193	2	10A	economy	f
194	2	10B	economy	f
195	2	10C	economy	f
196	2	10D	economy	f
197	2	10E	economy	f
198	2	10F	economy	f
199	2	11A	economy	f
200	2	11B	economy	f
201	2	11C	economy	f
202	2	11D	economy	f
203	2	11E	economy	f
204	2	11F	economy	f
205	2	12A	economy	f
206	2	12B	economy	f
207	2	12C	economy	f
208	2	12D	economy	f
209	2	12E	economy	f
210	2	12F	economy	f
211	2	13A	economy	f
212	2	13B	economy	f
213	2	13C	economy	f
214	2	13D	economy	f
215	2	13E	economy	f
216	2	13F	economy	f
217	2	14A	economy	t
218	2	14B	economy	t
219	2	14C	economy	t
220	2	14D	economy	t
221	2	14E	economy	t
222	2	14F	economy	t
223	2	15A	economy	f
224	2	15B	economy	f
225	2	15C	economy	f
226	2	15D	economy	f
227	2	15E	economy	f
228	2	15F	economy	f
229	2	16A	economy	f
230	2	16B	economy	f
231	2	16C	economy	f
232	2	16D	economy	f
233	2	16E	economy	f
234	2	16F	economy	f
235	2	17A	economy	f
236	2	17B	economy	f
237	2	17C	economy	f
238	2	17D	economy	f
239	2	17E	economy	f
240	2	17F	economy	f
241	2	18A	economy	f
242	2	18B	economy	f
243	2	18C	economy	f
244	2	18D	economy	f
245	2	18E	economy	f
246	2	18F	economy	f
247	2	19A	economy	f
248	2	19B	economy	f
249	2	19C	economy	f
250	2	19D	economy	f
251	2	19E	economy	f
252	2	19F	economy	f
253	2	20A	economy	f
254	2	20B	economy	f
255	2	20C	economy	f
256	2	20D	economy	f
257	2	20E	economy	f
258	2	20F	economy	f
259	2	21A	economy	f
260	2	21B	economy	f
261	2	21C	economy	f
262	2	21D	economy	f
263	2	21E	economy	f
264	2	21F	economy	f
265	2	22A	economy	f
266	2	22B	economy	f
267	2	22C	economy	f
268	2	22D	economy	f
269	2	22E	economy	f
270	2	22F	economy	f
271	2	23A	economy	f
272	2	23B	economy	f
273	2	23C	economy	f
274	2	23D	economy	f
275	2	23E	economy	f
276	2	23F	economy	f
277	2	24A	economy	f
278	2	24B	economy	f
279	2	24C	economy	f
280	2	24D	economy	f
281	2	24E	economy	f
282	2	24F	economy	f
283	2	25A	economy	f
284	2	25B	economy	f
285	2	25C	economy	f
286	2	25D	economy	f
287	2	25E	economy	f
288	2	25F	economy	f
289	3	1A	first	f
290	3	1B	first	f
291	3	1C	first	f
292	3	1D	first	f
293	3	1E	first	f
294	3	1F	first	f
295	3	2A	first	f
296	3	2B	first	f
297	3	2C	first	f
298	3	2D	first	f
299	3	2E	first	f
300	3	2F	first	f
301	3	3A	first	f
302	3	3B	first	f
303	3	3C	first	f
304	3	3D	first	f
305	3	3E	first	f
306	3	3F	first	f
307	3	4A	business	f
308	3	4B	business	f
309	3	4C	business	f
310	3	4D	business	f
311	3	4E	business	f
312	3	4F	business	f
313	3	5A	business	f
314	3	5B	business	f
315	3	5C	business	f
316	3	5D	business	f
317	3	5E	business	f
318	3	5F	business	f
319	3	6A	business	f
320	3	6B	business	f
321	3	6C	business	f
322	3	6D	business	f
323	3	6E	business	f
324	3	6F	business	f
325	3	7A	business	f
326	3	7B	business	f
327	3	7C	business	f
328	3	7D	business	f
329	3	7E	business	f
330	3	7F	business	f
331	3	8A	business	f
332	3	8B	business	f
333	3	8C	business	f
334	3	8D	business	f
335	3	8E	business	f
336	3	8F	business	f
337	3	9A	economy	f
338	3	9B	economy	f
339	3	9C	economy	f
340	3	9D	economy	f
341	3	9E	economy	f
342	3	9F	economy	f
343	3	10A	economy	f
344	3	10B	economy	f
345	3	10C	economy	f
346	3	10D	economy	f
347	3	10E	economy	f
348	3	10F	economy	f
349	3	11A	economy	f
350	3	11B	economy	f
351	3	11C	economy	f
352	3	11D	economy	f
353	3	11E	economy	f
354	3	11F	economy	f
355	3	12A	economy	f
356	3	12B	economy	f
357	3	12C	economy	f
358	3	12D	economy	f
359	3	12E	economy	f
360	3	12F	economy	f
361	3	13A	economy	f
362	3	13B	economy	f
363	3	13C	economy	f
364	3	13D	economy	f
365	3	13E	economy	f
366	3	13F	economy	f
367	3	14A	economy	f
368	3	14B	economy	f
369	3	14C	economy	f
370	3	14D	economy	f
371	3	14E	economy	f
372	3	14F	economy	f
373	3	15A	economy	f
374	3	15B	economy	f
375	3	15C	economy	f
376	3	15D	economy	f
377	3	15E	economy	f
378	3	15F	economy	f
379	3	16A	economy	t
380	3	16B	economy	t
381	3	16C	economy	t
382	3	16D	economy	t
383	3	16E	economy	t
384	3	16F	economy	t
385	3	17A	economy	t
386	3	17B	economy	t
387	3	17C	economy	t
388	3	17D	economy	t
389	3	17E	economy	t
390	3	17F	economy	t
391	3	18A	economy	f
392	3	18B	economy	f
393	3	18C	economy	f
394	3	18D	economy	f
395	3	18E	economy	f
396	3	18F	economy	f
397	3	19A	economy	f
398	3	19B	economy	f
399	3	19C	economy	f
400	3	19D	economy	f
401	3	19E	economy	f
402	3	19F	economy	f
403	3	20A	economy	f
404	3	20B	economy	f
405	3	20C	economy	f
406	3	20D	economy	f
407	3	20E	economy	f
408	3	20F	economy	f
409	3	21A	economy	f
410	3	21B	economy	f
411	3	21C	economy	f
412	3	21D	economy	f
413	3	21E	economy	f
414	3	21F	economy	f
415	3	22A	economy	f
416	3	22B	economy	f
417	3	22C	economy	f
418	3	22D	economy	f
419	3	22E	economy	f
420	3	22F	economy	f
421	3	23A	economy	f
422	3	23B	economy	f
423	3	23C	economy	f
424	3	23D	economy	f
425	3	23E	economy	f
426	3	23F	economy	f
427	3	24A	economy	f
428	3	24B	economy	f
429	3	24C	economy	f
430	3	24D	economy	f
431	3	24E	economy	f
432	3	24F	economy	f
433	3	25A	economy	f
434	3	25B	economy	f
435	3	25C	economy	f
436	3	25D	economy	f
437	3	25E	economy	f
438	3	25F	economy	f
439	3	26A	economy	f
440	3	26B	economy	f
441	3	26C	economy	f
442	3	26D	economy	f
443	3	26E	economy	f
444	3	26F	economy	f
445	3	27A	economy	f
446	3	27B	economy	f
447	3	27C	economy	f
448	3	27D	economy	f
449	3	27E	economy	f
450	3	27F	economy	f
451	3	28A	economy	f
452	3	28B	economy	f
453	3	28C	economy	f
454	3	28D	economy	f
455	3	28E	economy	f
456	3	28F	economy	f
457	3	29A	economy	f
458	3	29B	economy	f
459	3	29C	economy	f
460	3	29D	economy	f
461	3	29E	economy	f
462	3	29F	economy	f
463	3	30A	economy	f
464	3	30B	economy	f
465	3	30C	economy	f
466	3	30D	economy	f
467	3	30E	economy	f
468	3	30F	economy	f
469	3	31A	economy	f
470	3	31B	economy	f
471	3	31C	economy	f
472	3	31D	economy	f
473	3	31E	economy	f
474	3	31F	economy	f
475	4	1A	first	f
476	4	1B	first	f
477	4	1C	first	f
478	4	1D	first	f
479	4	1E	first	f
480	4	1F	first	f
481	4	1G	first	f
482	4	1H	first	f
483	4	1K	first	f
484	4	2A	first	f
485	4	2B	first	f
486	4	2C	first	f
487	4	2D	first	f
488	4	2E	first	f
489	4	2F	first	f
490	4	2G	first	f
491	4	2H	first	f
492	4	2K	first	f
493	4	3A	first	f
494	4	3B	first	f
495	4	3C	first	f
496	4	3D	first	f
497	4	3E	first	f
498	4	3F	first	f
499	4	3G	first	f
500	4	3H	first	f
501	4	3K	first	f
502	4	4A	first	f
503	4	4B	first	f
504	4	4C	first	f
505	4	4D	first	f
506	4	4E	first	f
507	4	4F	first	f
508	4	4G	first	f
509	4	4H	first	f
510	4	4K	first	f
511	4	5A	business	f
512	4	5B	business	f
513	4	5C	business	f
514	4	5D	business	f
515	4	5E	business	f
516	4	5F	business	f
517	4	5G	business	f
518	4	5H	business	f
519	4	5K	business	f
520	4	6A	business	f
521	4	6B	business	f
522	4	6C	business	f
523	4	6D	business	f
524	4	6E	business	f
525	4	6F	business	f
526	4	6G	business	f
527	4	6H	business	f
528	4	6K	business	f
529	4	7A	business	f
530	4	7B	business	f
531	4	7C	business	f
532	4	7D	business	f
533	4	7E	business	f
534	4	7F	business	f
535	4	7G	business	f
536	4	7H	business	f
537	4	7K	business	f
538	4	8A	business	f
539	4	8B	business	f
540	4	8C	business	f
541	4	8D	business	f
542	4	8E	business	f
543	4	8F	business	f
544	4	8G	business	f
545	4	8H	business	f
546	4	8K	business	f
547	4	9A	business	f
548	4	9B	business	f
549	4	9C	business	f
550	4	9D	business	f
551	4	9E	business	f
552	4	9F	business	f
553	4	9G	business	f
554	4	9H	business	f
555	4	9K	business	f
556	4	10A	business	f
557	4	10B	business	f
558	4	10C	business	f
559	4	10D	business	f
560	4	10E	business	f
561	4	10F	business	f
562	4	10G	business	f
563	4	10H	business	f
564	4	10K	business	f
565	4	11A	economy	f
566	4	11B	economy	f
567	4	11C	economy	f
568	4	11D	economy	f
569	4	11E	economy	f
570	4	11F	economy	f
571	4	11G	economy	f
572	4	11H	economy	f
573	4	11K	economy	f
574	4	12A	economy	t
575	4	12B	economy	t
576	4	12C	economy	t
577	4	12D	economy	t
578	4	12E	economy	t
579	4	12F	economy	t
580	4	12G	economy	t
581	4	12H	economy	t
582	4	12K	economy	t
583	4	13A	economy	f
584	4	13B	economy	f
585	4	13C	economy	f
586	4	13D	economy	f
587	4	13E	economy	f
588	4	13F	economy	f
589	4	13G	economy	f
590	4	13H	economy	f
591	4	13K	economy	f
592	4	14A	economy	f
593	4	14B	economy	f
594	4	14C	economy	f
595	4	14D	economy	f
596	4	14E	economy	f
597	4	14F	economy	f
598	4	14G	economy	f
599	4	14H	economy	f
600	4	14K	economy	f
601	4	15A	economy	f
602	4	15B	economy	f
603	4	15C	economy	f
604	4	15D	economy	f
605	4	15E	economy	f
606	4	15F	economy	f
607	4	15G	economy	f
608	4	15H	economy	f
609	4	15K	economy	f
610	4	16A	economy	f
611	4	16B	economy	f
612	4	16C	economy	f
613	4	16D	economy	f
614	4	16E	economy	f
615	4	16F	economy	f
616	4	16G	economy	f
617	4	16H	economy	f
618	4	16K	economy	f
619	4	17A	economy	f
620	4	17B	economy	f
621	4	17C	economy	f
622	4	17D	economy	f
623	4	17E	economy	f
624	4	17F	economy	f
625	4	17G	economy	f
626	4	17H	economy	f
627	4	17K	economy	f
628	4	18A	economy	f
629	4	18B	economy	f
630	4	18C	economy	f
631	4	18D	economy	f
632	4	18E	economy	f
633	4	18F	economy	f
634	4	18G	economy	f
635	4	18H	economy	f
636	4	18K	economy	f
637	4	19A	economy	f
638	4	19B	economy	f
639	4	19C	economy	f
640	4	19D	economy	f
641	4	19E	economy	f
642	4	19F	economy	f
643	4	19G	economy	f
644	4	19H	economy	f
645	4	19K	economy	f
646	4	20A	economy	f
647	4	20B	economy	f
648	4	20C	economy	f
649	4	20D	economy	f
650	4	20E	economy	f
651	4	20F	economy	f
652	4	20G	economy	f
653	4	20H	economy	f
654	4	20K	economy	f
655	4	21A	economy	f
656	4	21B	economy	f
657	4	21C	economy	f
658	4	21D	economy	f
659	4	21E	economy	f
660	4	21F	economy	f
661	4	21G	economy	f
662	4	21H	economy	f
663	4	21K	economy	f
664	4	22A	economy	f
665	4	22B	economy	f
666	4	22C	economy	f
667	4	22D	economy	f
668	4	22E	economy	f
669	4	22F	economy	f
670	4	22G	economy	f
671	4	22H	economy	f
672	4	22K	economy	f
673	4	23A	economy	f
674	4	23B	economy	f
675	4	23C	economy	f
676	4	23D	economy	f
677	4	23E	economy	f
678	4	23F	economy	f
679	4	23G	economy	f
680	4	23H	economy	f
681	4	23K	economy	f
682	4	24A	economy	t
683	4	24B	economy	t
684	4	24C	economy	t
685	4	24D	economy	t
686	4	24E	economy	t
687	4	24F	economy	t
688	4	24G	economy	t
689	4	24H	economy	t
690	4	24K	economy	t
691	4	25A	economy	f
692	4	25B	economy	f
693	4	25C	economy	f
694	4	25D	economy	f
695	4	25E	economy	f
696	4	25F	economy	f
697	4	25G	economy	f
698	4	25H	economy	f
699	4	25K	economy	f
700	4	26A	economy	f
701	4	26B	economy	f
702	4	26C	economy	f
703	4	26D	economy	f
704	4	26E	economy	f
705	4	26F	economy	f
706	4	26G	economy	f
707	4	26H	economy	f
708	4	26K	economy	f
709	4	27A	economy	f
710	4	27B	economy	f
711	4	27C	economy	f
712	4	27D	economy	f
713	4	27E	economy	f
714	4	27F	economy	f
715	4	27G	economy	f
716	4	27H	economy	f
717	4	27K	economy	f
718	4	28A	economy	f
719	4	28B	economy	f
720	4	28C	economy	f
721	4	28D	economy	f
722	4	28E	economy	f
723	4	28F	economy	f
724	4	28G	economy	f
725	4	28H	economy	f
726	4	28K	economy	f
727	4	29A	economy	f
728	4	29B	economy	f
729	4	29C	economy	f
730	4	29D	economy	f
731	4	29E	economy	f
732	4	29F	economy	f
733	4	29G	economy	f
734	4	29H	economy	f
735	4	29K	economy	f
736	4	30A	economy	f
737	4	30B	economy	f
738	4	30C	economy	f
739	4	30D	economy	f
740	4	30E	economy	f
741	4	30F	economy	f
742	4	30G	economy	f
743	4	30H	economy	f
744	4	30K	economy	f
745	4	31A	economy	f
746	4	31B	economy	f
747	4	31C	economy	f
748	4	31D	economy	f
749	4	31E	economy	f
750	4	31F	economy	f
751	4	31G	economy	f
752	4	31H	economy	f
753	4	31K	economy	f
754	5	1A	business	f
755	5	1B	business	f
756	5	1C	business	f
757	5	1D	business	f
758	5	1E	business	f
759	5	2A	business	f
760	5	2B	business	f
761	5	2C	business	f
762	5	2D	business	f
763	5	2E	business	f
764	5	3A	business	f
765	5	3B	business	f
766	5	3C	business	f
767	5	3D	business	f
768	5	3E	business	f
769	5	4A	economy	f
770	5	4B	economy	f
771	5	4C	economy	f
772	5	4D	economy	f
773	5	4E	economy	f
774	5	5A	economy	f
775	5	5B	economy	f
776	5	5C	economy	f
777	5	5D	economy	f
778	5	5E	economy	f
779	5	6A	economy	f
780	5	6B	economy	f
781	5	6C	economy	f
782	5	6D	economy	f
783	5	6E	economy	f
784	5	7A	economy	f
785	5	7B	economy	f
786	5	7C	economy	f
787	5	7D	economy	f
788	5	7E	economy	f
789	5	8A	economy	f
790	5	8B	economy	f
791	5	8C	economy	f
792	5	8D	economy	f
793	5	8E	economy	f
794	5	9A	economy	f
795	5	9B	economy	f
796	5	9C	economy	f
797	5	9D	economy	f
798	5	9E	economy	f
799	5	10A	economy	f
800	5	10B	economy	f
801	5	10C	economy	f
802	5	10D	economy	f
803	5	10E	economy	f
804	5	11A	economy	t
805	5	11B	economy	t
806	5	11C	economy	t
807	5	11D	economy	t
808	5	11E	economy	t
809	5	12A	economy	f
810	5	12B	economy	f
811	5	12C	economy	f
812	5	12D	economy	f
813	5	12E	economy	f
814	5	13A	economy	f
815	5	13B	economy	f
816	5	13C	economy	f
817	5	13D	economy	f
818	5	13E	economy	f
819	5	14A	economy	f
820	5	14B	economy	f
821	5	14C	economy	f
822	5	14D	economy	f
823	5	14E	economy	f
824	5	15A	economy	f
825	5	15B	economy	f
826	5	15C	economy	f
827	5	15D	economy	f
828	5	15E	economy	f
829	5	16A	economy	f
830	5	16B	economy	f
831	5	16C	economy	f
832	5	16D	economy	f
833	5	16E	economy	f
834	5	17A	economy	f
835	5	17B	economy	f
836	5	17C	economy	f
837	5	17D	economy	f
838	5	17E	economy	f
839	5	18A	economy	f
840	5	18B	economy	f
841	5	18C	economy	f
842	5	18D	economy	f
843	5	18E	economy	f
844	5	19A	economy	f
845	5	19B	economy	f
846	5	19C	economy	f
847	5	19D	economy	f
848	5	19E	economy	f
849	5	20A	economy	f
850	5	20B	economy	f
851	5	20C	economy	f
852	5	20D	economy	f
853	5	20E	economy	f
854	6	1A	business	f
855	6	1B	business	f
856	6	1C	business	f
857	6	1D	business	f
858	6	1E	business	f
859	6	1F	business	f
860	6	2A	business	f
861	6	2B	business	f
862	6	2C	business	f
863	6	2D	business	f
864	6	2E	business	f
865	6	2F	business	f
866	6	3A	business	f
867	6	3B	business	f
868	6	3C	business	f
869	6	3D	business	f
870	6	3E	business	f
871	6	3F	business	f
872	6	4A	business	f
873	6	4B	business	f
874	6	4C	business	f
875	6	4D	business	f
876	6	4E	business	f
877	6	4F	business	f
878	6	5A	economy	f
879	6	5B	economy	f
880	6	5C	economy	f
881	6	5D	economy	f
882	6	5E	economy	f
883	6	5F	economy	f
884	6	6A	economy	f
885	6	6B	economy	f
886	6	6C	economy	f
887	6	6D	economy	f
888	6	6E	economy	f
889	6	6F	economy	f
890	6	7A	economy	f
891	6	7B	economy	f
892	6	7C	economy	f
893	6	7D	economy	f
894	6	7E	economy	f
895	6	7F	economy	f
896	6	8A	economy	f
897	6	8B	economy	f
898	6	8C	economy	f
899	6	8D	economy	f
900	6	8E	economy	f
901	6	8F	economy	f
902	6	9A	economy	f
903	6	9B	economy	f
904	6	9C	economy	f
905	6	9D	economy	f
906	6	9E	economy	f
907	6	9F	economy	f
908	6	10A	economy	f
909	6	10B	economy	f
910	6	10C	economy	f
911	6	10D	economy	f
912	6	10E	economy	f
913	6	10F	economy	f
914	6	11A	economy	f
915	6	11B	economy	f
916	6	11C	economy	f
917	6	11D	economy	f
918	6	11E	economy	f
919	6	11F	economy	f
920	6	12A	economy	f
921	6	12B	economy	f
922	6	12C	economy	f
923	6	12D	economy	f
924	6	12E	economy	f
925	6	12F	economy	f
926	6	13A	economy	t
927	6	13B	economy	t
928	6	13C	economy	t
929	6	13D	economy	t
930	6	13E	economy	t
931	6	13F	economy	t
932	6	14A	economy	t
933	6	14B	economy	t
934	6	14C	economy	t
935	6	14D	economy	t
936	6	14E	economy	t
937	6	14F	economy	t
938	6	15A	economy	f
939	6	15B	economy	f
940	6	15C	economy	f
941	6	15D	economy	f
942	6	15E	economy	f
943	6	15F	economy	f
944	6	16A	economy	f
945	6	16B	economy	f
946	6	16C	economy	f
947	6	16D	economy	f
948	6	16E	economy	f
949	6	16F	economy	f
950	6	17A	economy	f
951	6	17B	economy	f
952	6	17C	economy	f
953	6	17D	economy	f
954	6	17E	economy	f
955	6	17F	economy	f
956	6	18A	economy	f
957	6	18B	economy	f
958	6	18C	economy	f
959	6	18D	economy	f
960	6	18E	economy	f
961	6	18F	economy	f
962	6	19A	economy	f
963	6	19B	economy	f
964	6	19C	economy	f
965	6	19D	economy	f
966	6	19E	economy	f
967	6	19F	economy	f
968	6	20A	economy	f
969	6	20B	economy	f
970	6	20C	economy	f
971	6	20D	economy	f
972	6	20E	economy	f
973	6	20F	economy	f
974	6	21A	economy	f
975	6	21B	economy	f
976	6	21C	economy	f
977	6	21D	economy	f
978	6	21E	economy	f
979	6	21F	economy	f
980	6	22A	economy	f
981	6	22B	economy	f
982	6	22C	economy	f
983	6	22D	economy	f
984	6	22E	economy	f
985	6	22F	economy	f
986	6	23A	economy	f
987	6	23B	economy	f
988	6	23C	economy	f
989	6	23D	economy	f
990	6	23E	economy	f
991	6	23F	economy	f
992	6	24A	economy	f
993	6	24B	economy	f
994	6	24C	economy	f
995	6	24D	economy	f
996	6	24E	economy	f
997	6	24F	economy	f
998	6	25A	economy	f
999	6	25B	economy	f
1000	6	25C	economy	f
1001	6	25D	economy	f
1002	6	25E	economy	f
1003	6	25F	economy	f
1004	6	26A	economy	f
1005	6	26B	economy	f
1006	6	26C	economy	f
1007	6	26D	economy	f
1008	6	26E	economy	f
1009	6	26F	economy	f
1010	6	27A	economy	f
1011	6	27B	economy	f
1012	6	27C	economy	f
1013	6	27D	economy	f
1014	6	27E	economy	f
1015	6	27F	economy	f
1016	7	1A	first	f
1017	7	1B	first	f
1018	7	1C	first	f
1019	7	1D	first	f
1020	7	1E	first	f
1021	7	1F	first	f
1022	7	1G	first	f
1023	7	1H	first	f
1024	7	1K	first	f
1025	7	2A	first	f
1026	7	2B	first	f
1027	7	2C	first	f
1028	7	2D	first	f
1029	7	2E	first	f
1030	7	2F	first	f
1031	7	2G	first	f
1032	7	2H	first	f
1033	7	2K	first	f
1034	7	3A	first	f
1035	7	3B	first	f
1036	7	3C	first	f
1037	7	3D	first	f
1038	7	3E	first	f
1039	7	3F	first	f
1040	7	3G	first	f
1041	7	3H	first	f
1042	7	3K	first	f
1043	7	4A	first	f
1044	7	4B	first	f
1045	7	4C	first	f
1046	7	4D	first	f
1047	7	4E	first	f
1048	7	4F	first	f
1049	7	4G	first	f
1050	7	4H	first	f
1051	7	4K	first	f
1052	7	5A	business	f
1053	7	5B	business	f
1054	7	5C	business	f
1055	7	5D	business	f
1056	7	5E	business	f
1057	7	5F	business	f
1058	7	5G	business	f
1059	7	5H	business	f
1060	7	5K	business	f
1061	7	6A	business	f
1062	7	6B	business	f
1063	7	6C	business	f
1064	7	6D	business	f
1065	7	6E	business	f
1066	7	6F	business	f
1067	7	6G	business	f
1068	7	6H	business	f
1069	7	6K	business	f
1070	7	7A	business	f
1071	7	7B	business	f
1072	7	7C	business	f
1073	7	7D	business	f
1074	7	7E	business	f
1075	7	7F	business	f
1076	7	7G	business	f
1077	7	7H	business	f
1078	7	7K	business	f
1079	7	8A	business	f
1080	7	8B	business	f
1081	7	8C	business	f
1082	7	8D	business	f
1083	7	8E	business	f
1084	7	8F	business	f
1085	7	8G	business	f
1086	7	8H	business	f
1087	7	8K	business	f
1088	7	9A	business	f
1089	7	9B	business	f
1090	7	9C	business	f
1091	7	9D	business	f
1092	7	9E	business	f
1093	7	9F	business	f
1094	7	9G	business	f
1095	7	9H	business	f
1096	7	9K	business	f
1097	7	10A	business	f
1098	7	10B	business	f
1099	7	10C	business	f
1100	7	10D	business	f
1101	7	10E	business	f
1102	7	10F	business	f
1103	7	10G	business	f
1104	7	10H	business	f
1105	7	10K	business	f
1106	7	11A	business	f
1107	7	11B	business	f
1108	7	11C	business	f
1109	7	11D	business	f
1110	7	11E	business	f
1111	7	11F	business	f
1112	7	11G	business	f
1113	7	11H	business	f
1114	7	11K	business	f
1115	7	12A	business	f
1116	7	12B	business	f
1117	7	12C	business	f
1118	7	12D	business	f
1119	7	12E	business	f
1120	7	12F	business	f
1121	7	12G	business	f
1122	7	12H	business	f
1123	7	12K	business	f
1124	7	13A	economy	f
1125	7	13B	economy	f
1126	7	13C	economy	f
1127	7	13D	economy	f
1128	7	13E	economy	f
1129	7	13F	economy	f
1130	7	13G	economy	f
1131	7	13H	economy	f
1132	7	13K	economy	f
1133	7	14A	economy	f
1134	7	14B	economy	f
1135	7	14C	economy	f
1136	7	14D	economy	f
1137	7	14E	economy	f
1138	7	14F	economy	f
1139	7	14G	economy	f
1140	7	14H	economy	f
1141	7	14K	economy	f
1142	7	15A	economy	t
1143	7	15B	economy	t
1144	7	15C	economy	t
1145	7	15D	economy	t
1146	7	15E	economy	t
1147	7	15F	economy	t
1148	7	15G	economy	t
1149	7	15H	economy	t
1150	7	15K	economy	t
1151	7	16A	economy	t
1152	7	16B	economy	t
1153	7	16C	economy	t
1154	7	16D	economy	t
1155	7	16E	economy	t
1156	7	16F	economy	t
1157	7	16G	economy	t
1158	7	16H	economy	t
1159	7	16K	economy	t
1160	7	17A	economy	f
1161	7	17B	economy	f
1162	7	17C	economy	f
1163	7	17D	economy	f
1164	7	17E	economy	f
1165	7	17F	economy	f
1166	7	17G	economy	f
1167	7	17H	economy	f
1168	7	17K	economy	f
1169	7	18A	economy	f
1170	7	18B	economy	f
1171	7	18C	economy	f
1172	7	18D	economy	f
1173	7	18E	economy	f
1174	7	18F	economy	f
1175	7	18G	economy	f
1176	7	18H	economy	f
1177	7	18K	economy	f
1178	7	19A	economy	f
1179	7	19B	economy	f
1180	7	19C	economy	f
1181	7	19D	economy	f
1182	7	19E	economy	f
1183	7	19F	economy	f
1184	7	19G	economy	f
1185	7	19H	economy	f
1186	7	19K	economy	f
1187	7	20A	economy	f
1188	7	20B	economy	f
1189	7	20C	economy	f
1190	7	20D	economy	f
1191	7	20E	economy	f
1192	7	20F	economy	f
1193	7	20G	economy	f
1194	7	20H	economy	f
1195	7	20K	economy	f
1196	7	21A	economy	f
1197	7	21B	economy	f
1198	7	21C	economy	f
1199	7	21D	economy	f
1200	7	21E	economy	f
1201	7	21F	economy	f
1202	7	21G	economy	f
1203	7	21H	economy	f
1204	7	21K	economy	f
1205	7	22A	economy	f
1206	7	22B	economy	f
1207	7	22C	economy	f
1208	7	22D	economy	f
1209	7	22E	economy	f
1210	7	22F	economy	f
1211	7	22G	economy	f
1212	7	22H	economy	f
1213	7	22K	economy	f
1214	7	23A	economy	f
1215	7	23B	economy	f
1216	7	23C	economy	f
1217	7	23D	economy	f
1218	7	23E	economy	f
1219	7	23F	economy	f
1220	7	23G	economy	f
1221	7	23H	economy	f
1222	7	23K	economy	f
1223	7	24A	economy	f
1224	7	24B	economy	f
1225	7	24C	economy	f
1226	7	24D	economy	f
1227	7	24E	economy	f
1228	7	24F	economy	f
1229	7	24G	economy	f
1230	7	24H	economy	f
1231	7	24K	economy	f
1232	7	25A	economy	f
1233	7	25B	economy	f
1234	7	25C	economy	f
1235	7	25D	economy	f
1236	7	25E	economy	f
1237	7	25F	economy	f
1238	7	25G	economy	f
1239	7	25H	economy	f
1240	7	25K	economy	f
1241	7	26A	economy	f
1242	7	26B	economy	f
1243	7	26C	economy	f
1244	7	26D	economy	f
1245	7	26E	economy	f
1246	7	26F	economy	f
1247	7	26G	economy	f
1248	7	26H	economy	f
1249	7	26K	economy	f
1250	7	27A	economy	f
1251	7	27B	economy	f
1252	7	27C	economy	f
1253	7	27D	economy	f
1254	7	27E	economy	f
1255	7	27F	economy	f
1256	7	27G	economy	f
1257	7	27H	economy	f
1258	7	27K	economy	f
1259	7	28A	economy	f
1260	7	28B	economy	f
1261	7	28C	economy	f
1262	7	28D	economy	f
1263	7	28E	economy	f
1264	7	28F	economy	f
1265	7	28G	economy	f
1266	7	28H	economy	f
1267	7	28K	economy	f
1268	7	29A	economy	f
1269	7	29B	economy	f
1270	7	29C	economy	f
1271	7	29D	economy	f
1272	7	29E	economy	f
1273	7	29F	economy	f
1274	7	29G	economy	f
1275	7	29H	economy	f
1276	7	29K	economy	f
1277	7	30A	economy	t
1278	7	30B	economy	t
1279	7	30C	economy	t
1280	7	30D	economy	t
1281	7	30E	economy	t
1282	7	30F	economy	t
1283	7	30G	economy	t
1284	7	30H	economy	t
1285	7	30K	economy	t
1286	7	31A	economy	f
1287	7	31B	economy	f
1288	7	31C	economy	f
1289	7	31D	economy	f
1290	7	31E	economy	f
1291	7	31F	economy	f
1292	7	31G	economy	f
1293	7	31H	economy	f
1294	7	31K	economy	f
1295	7	32A	economy	f
1296	7	32B	economy	f
1297	7	32C	economy	f
1298	7	32D	economy	f
1299	7	32E	economy	f
1300	7	32F	economy	f
1301	7	32G	economy	f
1302	7	32H	economy	f
1303	7	32K	economy	f
1304	7	33A	economy	f
1305	7	33B	economy	f
1306	7	33C	economy	f
1307	7	33D	economy	f
1308	7	33E	economy	f
1309	7	33F	economy	f
1310	7	33G	economy	f
1311	7	33H	economy	f
1312	7	33K	economy	f
1313	7	34A	economy	f
1314	7	34B	economy	f
1315	7	34C	economy	f
1316	7	34D	economy	f
1317	7	34E	economy	f
1318	7	34F	economy	f
1319	7	34G	economy	f
1320	7	34H	economy	f
1321	7	34K	economy	f
1322	7	35A	economy	f
1323	7	35B	economy	f
1324	7	35C	economy	f
1325	7	35D	economy	f
1326	7	35E	economy	f
1327	7	35F	economy	f
1328	7	35G	economy	f
1329	7	35H	economy	f
1330	7	35K	economy	f
1331	7	36A	economy	f
1332	7	36B	economy	f
1333	7	36C	economy	f
1334	7	36D	economy	f
1335	7	36E	economy	f
1336	7	36F	economy	f
1337	7	36G	economy	f
1338	7	36H	economy	f
1339	7	36K	economy	f
1340	7	37A	economy	f
1341	7	37B	economy	f
1342	7	37C	economy	f
1343	7	37D	economy	f
1344	7	37E	economy	f
1345	7	37F	economy	f
1346	7	37G	economy	f
1347	7	37H	economy	f
1348	7	37K	economy	f
1349	7	38A	economy	f
1350	7	38B	economy	f
1351	7	38C	economy	f
1352	7	38D	economy	f
1353	7	38E	economy	f
1354	7	38F	economy	f
1355	7	38G	economy	f
1356	7	38H	economy	f
1357	7	38K	economy	f
1358	7	39A	economy	f
1359	7	39B	economy	f
1360	7	39C	economy	f
1361	7	39D	economy	f
1362	7	39E	economy	f
1363	7	39F	economy	f
1364	7	39G	economy	f
1365	7	39H	economy	f
1366	7	39K	economy	f
1367	7	40A	economy	f
1368	7	40B	economy	f
1369	7	40C	economy	f
1370	7	40D	economy	f
1371	7	40E	economy	f
1372	7	40F	economy	f
1373	7	40G	economy	f
1374	7	40H	economy	f
1375	7	40K	economy	f
1376	8	1A	first	f
1377	8	1B	first	f
1378	8	1C	first	f
1379	8	1D	first	f
1380	8	1E	first	f
1381	8	1F	first	f
1382	8	1G	first	f
1383	8	1H	first	f
1384	8	1K	first	f
1385	8	2A	first	f
1386	8	2B	first	f
1387	8	2C	first	f
1388	8	2D	first	f
1389	8	2E	first	f
1390	8	2F	first	f
1391	8	2G	first	f
1392	8	2H	first	f
1393	8	2K	first	f
1394	8	3A	first	f
1395	8	3B	first	f
1396	8	3C	first	f
1397	8	3D	first	f
1398	8	3E	first	f
1399	8	3F	first	f
1400	8	3G	first	f
1401	8	3H	first	f
1402	8	3K	first	f
1403	8	4A	business	f
1404	8	4B	business	f
1405	8	4C	business	f
1406	8	4D	business	f
1407	8	4E	business	f
1408	8	4F	business	f
1409	8	4G	business	f
1410	8	4H	business	f
1411	8	4K	business	f
1412	8	5A	business	f
1413	8	5B	business	f
1414	8	5C	business	f
1415	8	5D	business	f
1416	8	5E	business	f
1417	8	5F	business	f
1418	8	5G	business	f
1419	8	5H	business	f
1420	8	5K	business	f
1421	8	6A	business	f
1422	8	6B	business	f
1423	8	6C	business	f
1424	8	6D	business	f
1425	8	6E	business	f
1426	8	6F	business	f
1427	8	6G	business	f
1428	8	6H	business	f
1429	8	6K	business	f
1430	8	7A	business	f
1431	8	7B	business	f
1432	8	7C	business	f
1433	8	7D	business	f
1434	8	7E	business	f
1435	8	7F	business	f
1436	8	7G	business	f
1437	8	7H	business	f
1438	8	7K	business	f
1439	8	8A	business	f
1440	8	8B	business	f
1441	8	8C	business	f
1442	8	8D	business	f
1443	8	8E	business	f
1444	8	8F	business	f
1445	8	8G	business	f
1446	8	8H	business	f
1447	8	8K	business	f
1448	8	9A	business	f
1449	8	9B	business	f
1450	8	9C	business	f
1451	8	9D	business	f
1452	8	9E	business	f
1453	8	9F	business	f
1454	8	9G	business	f
1455	8	9H	business	f
1456	8	9K	business	f
1457	8	10A	business	f
1458	8	10B	business	f
1459	8	10C	business	f
1460	8	10D	business	f
1461	8	10E	business	f
1462	8	10F	business	f
1463	8	10G	business	f
1464	8	10H	business	f
1465	8	10K	business	f
1466	8	11A	economy	f
1467	8	11B	economy	f
1468	8	11C	economy	f
1469	8	11D	economy	f
1470	8	11E	economy	f
1471	8	11F	economy	f
1472	8	11G	economy	f
1473	8	11H	economy	f
1474	8	11K	economy	f
1475	8	12A	economy	f
1476	8	12B	economy	f
1477	8	12C	economy	f
1478	8	12D	economy	f
1479	8	12E	economy	f
1480	8	12F	economy	f
1481	8	12G	economy	f
1482	8	12H	economy	f
1483	8	12K	economy	f
1484	8	13A	economy	f
1485	8	13B	economy	f
1486	8	13C	economy	f
1487	8	13D	economy	f
1488	8	13E	economy	f
1489	8	13F	economy	f
1490	8	13G	economy	f
1491	8	13H	economy	f
1492	8	13K	economy	f
1493	8	14A	economy	t
1494	8	14B	economy	t
1495	8	14C	economy	t
1496	8	14D	economy	t
1497	8	14E	economy	t
1498	8	14F	economy	t
1499	8	14G	economy	t
1500	8	14H	economy	t
1501	8	14K	economy	t
1502	8	15A	economy	f
1503	8	15B	economy	f
1504	8	15C	economy	f
1505	8	15D	economy	f
1506	8	15E	economy	f
1507	8	15F	economy	f
1508	8	15G	economy	f
1509	8	15H	economy	f
1510	8	15K	economy	f
1511	8	16A	economy	f
1512	8	16B	economy	f
1513	8	16C	economy	f
1514	8	16D	economy	f
1515	8	16E	economy	f
1516	8	16F	economy	f
1517	8	16G	economy	f
1518	8	16H	economy	f
1519	8	16K	economy	f
1520	8	17A	economy	f
1521	8	17B	economy	f
1522	8	17C	economy	f
1523	8	17D	economy	f
1524	8	17E	economy	f
1525	8	17F	economy	f
1526	8	17G	economy	f
1527	8	17H	economy	f
1528	8	17K	economy	f
1529	8	18A	economy	f
1530	8	18B	economy	f
1531	8	18C	economy	f
1532	8	18D	economy	f
1533	8	18E	economy	f
1534	8	18F	economy	f
1535	8	18G	economy	f
1536	8	18H	economy	f
1537	8	18K	economy	f
1538	8	19A	economy	f
1539	8	19B	economy	f
1540	8	19C	economy	f
1541	8	19D	economy	f
1542	8	19E	economy	f
1543	8	19F	economy	f
1544	8	19G	economy	f
1545	8	19H	economy	f
1546	8	19K	economy	f
1547	8	20A	economy	f
1548	8	20B	economy	f
1549	8	20C	economy	f
1550	8	20D	economy	f
1551	8	20E	economy	f
1552	8	20F	economy	f
1553	8	20G	economy	f
1554	8	20H	economy	f
1555	8	20K	economy	f
1556	8	21A	economy	f
1557	8	21B	economy	f
1558	8	21C	economy	f
1559	8	21D	economy	f
1560	8	21E	economy	f
1561	8	21F	economy	f
1562	8	21G	economy	f
1563	8	21H	economy	f
1564	8	21K	economy	f
1565	8	22A	economy	f
1566	8	22B	economy	f
1567	8	22C	economy	f
1568	8	22D	economy	f
1569	8	22E	economy	f
1570	8	22F	economy	f
1571	8	22G	economy	f
1572	8	22H	economy	f
1573	8	22K	economy	f
1574	8	23A	economy	f
1575	8	23B	economy	f
1576	8	23C	economy	f
1577	8	23D	economy	f
1578	8	23E	economy	f
1579	8	23F	economy	f
1580	8	23G	economy	f
1581	8	23H	economy	f
1582	8	23K	economy	f
1583	8	24A	economy	f
1584	8	24B	economy	f
1585	8	24C	economy	f
1586	8	24D	economy	f
1587	8	24E	economy	f
1588	8	24F	economy	f
1589	8	24G	economy	f
1590	8	24H	economy	f
1591	8	24K	economy	f
1592	8	25A	economy	f
1593	8	25B	economy	f
1594	8	25C	economy	f
1595	8	25D	economy	f
1596	8	25E	economy	f
1597	8	25F	economy	f
1598	8	25G	economy	f
1599	8	25H	economy	f
1600	8	25K	economy	f
1601	8	26A	economy	f
1602	8	26B	economy	f
1603	8	26C	economy	f
1604	8	26D	economy	f
1605	8	26E	economy	f
1606	8	26F	economy	f
1607	8	26G	economy	f
1608	8	26H	economy	f
1609	8	26K	economy	f
1610	8	27A	economy	f
1611	8	27B	economy	f
1612	8	27C	economy	f
1613	8	27D	economy	f
1614	8	27E	economy	f
1615	8	27F	economy	f
1616	8	27G	economy	f
1617	8	27H	economy	f
1618	8	27K	economy	f
1619	8	28A	economy	f
1620	8	28B	economy	f
1621	8	28C	economy	f
1622	8	28D	economy	f
1623	8	28E	economy	f
1624	8	28F	economy	f
1625	8	28G	economy	f
1626	8	28H	economy	f
1627	8	28K	economy	f
1628	8	29A	economy	t
1629	8	29B	economy	t
1630	8	29C	economy	t
1631	8	29D	economy	t
1632	8	29E	economy	t
1633	8	29F	economy	t
1634	8	29G	economy	t
1635	8	29H	economy	t
1636	8	29K	economy	t
1637	8	30A	economy	f
1638	8	30B	economy	f
1639	8	30C	economy	f
1640	8	30D	economy	f
1641	8	30E	economy	f
1642	8	30F	economy	f
1643	8	30G	economy	f
1644	8	30H	economy	f
1645	8	30K	economy	f
1646	8	31A	economy	f
1647	8	31B	economy	f
1648	8	31C	economy	f
1649	8	31D	economy	f
1650	8	31E	economy	f
1651	8	31F	economy	f
1652	8	31G	economy	f
1653	8	31H	economy	f
1654	8	31K	economy	f
1655	8	32A	economy	f
1656	8	32B	economy	f
1657	8	32C	economy	f
1658	8	32D	economy	f
1659	8	32E	economy	f
1660	8	32F	economy	f
1661	8	32G	economy	f
1662	8	32H	economy	f
1663	8	32K	economy	f
1664	8	33A	economy	f
1665	8	33B	economy	f
1666	8	33C	economy	f
1667	8	33D	economy	f
1668	8	33E	economy	f
1669	8	33F	economy	f
1670	8	33G	economy	f
1671	8	33H	economy	f
1672	8	33K	economy	f
1673	8	34A	economy	f
1674	8	34B	economy	f
1675	8	34C	economy	f
1676	8	34D	economy	f
1677	8	34E	economy	f
1678	8	34F	economy	f
1679	8	34G	economy	f
1680	8	34H	economy	f
1681	8	34K	economy	f
1682	8	35A	economy	f
1683	8	35B	economy	f
1684	8	35C	economy	f
1685	8	35D	economy	f
1686	8	35E	economy	f
1687	8	35F	economy	f
1688	8	35G	economy	f
1689	8	35H	economy	f
1690	8	35K	economy	f
1691	8	36A	economy	f
1692	8	36B	economy	f
1693	8	36C	economy	f
1694	8	36D	economy	f
1695	8	36E	economy	f
1696	8	36F	economy	f
1697	8	36G	economy	f
1698	8	36H	economy	f
1699	8	36K	economy	f
1700	8	37A	economy	f
1701	8	37B	economy	f
1702	8	37C	economy	f
1703	8	37D	economy	f
1704	8	37E	economy	f
1705	8	37F	economy	f
1706	8	37G	economy	f
1707	8	37H	economy	f
1708	8	37K	economy	f
1709	8	38A	economy	f
1710	8	38B	economy	f
1711	8	38C	economy	f
1712	8	38D	economy	f
1713	8	38E	economy	f
1714	8	38F	economy	f
1715	8	38G	economy	f
1716	8	38H	economy	f
1717	8	38K	economy	f
\.


--
-- TOC entry 5122 (class 0 OID 16403)
-- Dependencies: 220
-- Data for Name: user_account; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_account (id, role_id, email, password, surname, name, patronymic, created_at, updated_at) FROM stdin;
1	1	ermakov@yandex.ru	827ccb0eea8a706c4c34a16891f84e7b	Ермаков	Эмир	Иванович	2025-06-23 07:42:02.603929	2025-06-23 07:42:02.603929
3	2	drozdova@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Дроздова	София	Ивановна	2025-06-23 08:19:52.541493	2025-06-23 08:19:52.541493
4	2	suharev@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Сухарев	Арсений	Владимирович	2025-06-23 08:21:14.118063	2025-06-23 08:21:14.118063
5	2	makarova@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Макарова	Агата	Максимовна	2025-06-23 08:25:21.35403	2025-06-23 08:25:21.35403
6	2	pankov@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Панков	Николай	Максимович	2025-06-23 08:26:29.997033	2025-06-23 08:26:29.997033
7	2	karpov@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Карпов	Данила	Максимович	2025-06-23 08:28:33.044275	2025-06-23 08:28:33.044275
8	5	kulagin@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Кулагин	Михаил	Иванович	2025-06-23 08:29:59.44138	2025-06-23 08:29:59.44138
9	5	nekrasova@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Некрасова	Мария	Матвеевна	2025-06-23 08:31:13.598387	2025-06-23 08:31:13.598387
10	5	zukov@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Жуков	Вадим	Артёмович	2025-06-23 08:32:21.013621	2025-06-23 08:32:21.013621
11	5	petrov@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Петров	Матвей	Маркович	2025-06-23 08:33:11.767902	2025-06-23 08:33:11.767902
12	5	frolova@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Фролова	Анастасия	Владиславовна	2025-06-23 08:34:22.026485	2025-06-23 08:34:22.026485
13	5	danilov@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Данилов	Тимофей	Егорович	2025-06-23 08:35:15.620517	2025-06-23 08:35:15.620517
14	6	kudryavceva@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Кудрявцева	Виктория	Платоновна	2025-06-23 08:37:04.407351	2025-06-23 08:37:04.407351
15	6	lebedeva@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Лебедева	Дарья	Александровна	2025-06-23 08:38:01.641547	2025-06-23 08:38:01.641547
16	6	pastuhov@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Пастухов	Георгий	Александрович	2025-06-23 08:39:09.392517	2025-06-23 08:39:09.392517
17	6	gerasimov@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Герасимов	Роман	Елисеевич	2025-06-23 08:40:37.571094	2025-06-23 08:40:37.571094
18	6	klimova@yandex.ru	e10adc3949ba59abbe56e057f20f883e	Климова	Дарья	Матвеевна	2025-06-23 08:41:24.529236	2025-06-23 08:41:24.529236
19	1	rodionov@yandex.ru	827ccb0eea8a706c4c34a16891f84e7b	Родионов	Николай	Иванович	2025-06-23 08:58:02.116003	2025-06-23 08:58:02.116003
20	1	zikov@yandex.ru	827ccb0eea8a706c4c34a16891f84e7b	Зыков	Фёдор	Романович	2025-06-23 09:06:01.37809	2025-06-23 09:06:01.37809
21	1	maltcev@yandex.ru	827ccb0eea8a706c4c34a16891f84e7b	Мальцев	Роман	Егорович	2025-06-23 09:11:06.775484	2025-06-23 09:11:06.775484
2	1	kor@yandex.ru	827ccb0eea8a706c4c34a16891f84e7b	Коршунов	Даниил	Евгеньевич	2025-06-23 07:55:33.272407	2025-06-23 07:55:33.272407
\.


--
-- TOC entry 5124 (class 0 OID 16420)
-- Dependencies: 222
-- Data for Name: user_passenger; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_passenger (id, user_id, passport_series_number, date_of_birth, gender) FROM stdin;
1	1	2349 812759	1976-02-06	male
2	2	2352 643565	1965-07-10	male
3	19	7477 485385	1971-07-20	male
4	20	8437 658743	1982-01-23	male
5	21	3464 356456	1992-02-29	male
\.


--
-- TOC entry 5132 (class 0 OID 16488)
-- Dependencies: 230
-- Data for Name: worker_details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker_details (id, user_id, airline_id, hired_at, position_details, is_password_changed, is_active) FROM stdin;
1	3	2	2021-05-20	Планировщик 1 категории	f	t
2	4	2	2016-10-20	Главный планировщик	f	t
3	5	2	2021-02-20	Планировщик 2 категории	f	t
4	6	2	2023-09-01	Планировщик 3 категории\t	f	t
5	7	2	2024-10-23	Планировщик стажер	f	t
6	8	2	2016-10-10	Командир воздушного судна	f	t
7	9	2	2015-03-19	Пилот инструктор	f	t
8	10	2	2020-12-01	Командир воздушного судна	f	t
9	11	2	2022-09-11	Второй пилот	f	t
10	12	2	2022-04-08	Второй пилот	f	t
11	13	2	2025-02-06	Пилот стажер	f	t
12	14	2	2015-12-12	Бортпроводник инструктор	f	t
13	15	2	2020-10-10	Старший бортпроводник	f	t
14	16	2	2019-12-12	Бортпроводник	f	t
15	17	2	2025-04-23	Бортпроводник стажер	f	t
16	18	2	2021-10-09	Бортпроводник	f	t
\.


--
-- TOC entry 5161 (class 0 OID 0)
-- Dependencies: 227
-- Name: airline_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.airline_id_seq', 5, true);


--
-- TOC entry 5162 (class 0 OID 0)
-- Dependencies: 233
-- Name: airplane_airline_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.airplane_airline_id_seq', 24, true);


--
-- TOC entry 5163 (class 0 OID 0)
-- Dependencies: 231
-- Name: airplane_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.airplane_id_seq', 8, true);


--
-- TOC entry 5164 (class 0 OID 0)
-- Dependencies: 225
-- Name: airport_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.airport_id_seq', 20, true);


--
-- TOC entry 5165 (class 0 OID 0)
-- Dependencies: 243
-- Name: booking_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.booking_id_seq', 1, true);


--
-- TOC entry 5166 (class 0 OID 0)
-- Dependencies: 245
-- Name: booking_passenger_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.booking_passenger_id_seq', 9, true);


--
-- TOC entry 5167 (class 0 OID 0)
-- Dependencies: 249
-- Name: charter_contact_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.charter_contact_id_seq', 1, false);


--
-- TOC entry 5168 (class 0 OID 0)
-- Dependencies: 247
-- Name: charter_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.charter_request_id_seq', 5, true);


--
-- TOC entry 5169 (class 0 OID 0)
-- Dependencies: 251
-- Name: connects_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.connects_id_seq', 7, true);


--
-- TOC entry 5170 (class 0 OID 0)
-- Dependencies: 241
-- Name: crew_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.crew_id_seq', 9, true);


--
-- TOC entry 5171 (class 0 OID 0)
-- Dependencies: 239
-- Name: flight_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.flight_id_seq', 5, true);


--
-- TOC entry 5172 (class 0 OID 0)
-- Dependencies: 237
-- Name: flight_status_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.flight_status_id_seq', 12, true);


--
-- TOC entry 5173 (class 0 OID 0)
-- Dependencies: 223
-- Name: passenger_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.passenger_details_id_seq', 9, true);


--
-- TOC entry 5174 (class 0 OID 0)
-- Dependencies: 217
-- Name: role_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.role_id_seq', 6, true);


--
-- TOC entry 5175 (class 0 OID 0)
-- Dependencies: 235
-- Name: seat_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.seat_id_seq', 1717, true);


--
-- TOC entry 5176 (class 0 OID 0)
-- Dependencies: 219
-- Name: user_account_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_account_id_seq', 21, true);


--
-- TOC entry 5177 (class 0 OID 0)
-- Dependencies: 221
-- Name: user_passenger_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_passenger_id_seq', 5, true);


--
-- TOC entry 5178 (class 0 OID 0)
-- Dependencies: 229
-- Name: worker_details_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.worker_details_id_seq', 16, true);


--
-- TOC entry 4888 (class 2606 OID 16476)
-- Name: airline airline_iata_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airline
    ADD CONSTRAINT airline_iata_key UNIQUE (iata);


--
-- TOC entry 4890 (class 2606 OID 16474)
-- Name: airline airline_icao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airline
    ADD CONSTRAINT airline_icao_key UNIQUE (icao);


--
-- TOC entry 4892 (class 2606 OID 16472)
-- Name: airline airline_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airline
    ADD CONSTRAINT airline_name_key UNIQUE (name);


--
-- TOC entry 4894 (class 2606 OID 16470)
-- Name: airline airline_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airline
    ADD CONSTRAINT airline_pkey PRIMARY KEY (id);


--
-- TOC entry 4906 (class 2606 OID 16530)
-- Name: airplane_airline airplane_airline_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airplane_airline
    ADD CONSTRAINT airplane_airline_pkey PRIMARY KEY (id);


--
-- TOC entry 4908 (class 2606 OID 16800)
-- Name: airplane_airline airplane_airline_registration_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airplane_airline
    ADD CONSTRAINT airplane_airline_registration_key UNIQUE (registration);


--
-- TOC entry 4900 (class 2606 OID 16522)
-- Name: airplane airplane_iata_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airplane
    ADD CONSTRAINT airplane_iata_key UNIQUE (iata);


--
-- TOC entry 4902 (class 2606 OID 16520)
-- Name: airplane airplane_icao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airplane
    ADD CONSTRAINT airplane_icao_key UNIQUE (icao);


--
-- TOC entry 4904 (class 2606 OID 16518)
-- Name: airplane airplane_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airplane
    ADD CONSTRAINT airplane_pkey PRIMARY KEY (id);


--
-- TOC entry 4880 (class 2606 OID 16462)
-- Name: airport airport_iata_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airport
    ADD CONSTRAINT airport_iata_key UNIQUE (iata);


--
-- TOC entry 4882 (class 2606 OID 16460)
-- Name: airport airport_icao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airport
    ADD CONSTRAINT airport_icao_key UNIQUE (icao);


--
-- TOC entry 4884 (class 2606 OID 16458)
-- Name: airport airport_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airport
    ADD CONSTRAINT airport_name_key UNIQUE (name);


--
-- TOC entry 4886 (class 2606 OID 16456)
-- Name: airport airport_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airport
    ADD CONSTRAINT airport_pkey PRIMARY KEY (id);


--
-- TOC entry 4931 (class 2606 OID 16622)
-- Name: booking_passenger booking_passenger_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_passenger
    ADD CONSTRAINT booking_passenger_pkey PRIMARY KEY (id);


--
-- TOC entry 4926 (class 2606 OID 16611)
-- Name: booking booking_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking
    ADD CONSTRAINT booking_pkey PRIMARY KEY (id);


--
-- TOC entry 4939 (class 2606 OID 16664)
-- Name: charter_contact charter_contact_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_contact
    ADD CONSTRAINT charter_contact_pkey PRIMARY KEY (id);


--
-- TOC entry 4935 (class 2606 OID 16648)
-- Name: charter_request charter_request_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_request
    ADD CONSTRAINT charter_request_pkey PRIMARY KEY (id);


--
-- TOC entry 4937 (class 2606 OID 16780)
-- Name: charter_request charter_request_request_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_request
    ADD CONSTRAINT charter_request_request_code_key UNIQUE (request_code);


--
-- TOC entry 4941 (class 2606 OID 16740)
-- Name: connect connects_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.connect
    ADD CONSTRAINT connects_pkey PRIMARY KEY (id);


--
-- TOC entry 4924 (class 2606 OID 16592)
-- Name: crew crew_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.crew
    ADD CONSTRAINT crew_pkey PRIMARY KEY (id);


--
-- TOC entry 4915 (class 2606 OID 16785)
-- Name: flight flight_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_code_key UNIQUE (flight_code);


--
-- TOC entry 4917 (class 2606 OID 16566)
-- Name: flight flight_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_pkey PRIMARY KEY (id);


--
-- TOC entry 4913 (class 2606 OID 16559)
-- Name: flight_status flight_status_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight_status
    ADD CONSTRAINT flight_status_pkey PRIMARY KEY (id);


--
-- TOC entry 4878 (class 2606 OID 16440)
-- Name: passenger_details passenger_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passenger_details
    ADD CONSTRAINT passenger_details_pkey PRIMARY KEY (id);


--
-- TOC entry 4868 (class 2606 OID 16401)
-- Name: role role_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT role_pkey PRIMARY KEY (id);


--
-- TOC entry 4911 (class 2606 OID 16548)
-- Name: seat seat_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat
    ADD CONSTRAINT seat_pkey PRIMARY KEY (id);


--
-- TOC entry 4870 (class 2606 OID 16413)
-- Name: user_account user_account_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_account
    ADD CONSTRAINT user_account_email_key UNIQUE (email);


--
-- TOC entry 4872 (class 2606 OID 16411)
-- Name: user_account user_account_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_account
    ADD CONSTRAINT user_account_pkey PRIMARY KEY (id);


--
-- TOC entry 4874 (class 2606 OID 16425)
-- Name: user_passenger user_passenger_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_passenger
    ADD CONSTRAINT user_passenger_pkey PRIMARY KEY (id);


--
-- TOC entry 4876 (class 2606 OID 16427)
-- Name: user_passenger user_passenger_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_passenger
    ADD CONSTRAINT user_passenger_user_id_key UNIQUE (user_id);


--
-- TOC entry 4896 (class 2606 OID 16492)
-- Name: worker_details worker_details_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_details
    ADD CONSTRAINT worker_details_pkey PRIMARY KEY (id);


--
-- TOC entry 4898 (class 2606 OID 16494)
-- Name: worker_details worker_details_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_details
    ADD CONSTRAINT worker_details_user_id_key UNIQUE (user_id);


--
-- TOC entry 4909 (class 1259 OID 16835)
-- Name: idx_airplane_airline_airline; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_airplane_airline_airline ON public.airplane_airline USING btree (airline_id);


--
-- TOC entry 4927 (class 1259 OID 16832)
-- Name: idx_booking_charter_request; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_booking_charter_request ON public.booking USING btree (charter_request_id);


--
-- TOC entry 4928 (class 1259 OID 16830)
-- Name: idx_booking_flight_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_booking_flight_id ON public.booking USING btree (flight_id);


--
-- TOC entry 4932 (class 1259 OID 16833)
-- Name: idx_booking_passenger_booking_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_booking_passenger_booking_id ON public.booking_passenger USING btree (booking_id);


--
-- TOC entry 4933 (class 1259 OID 16834)
-- Name: idx_booking_passenger_passenger_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_booking_passenger_passenger_id ON public.booking_passenger USING btree (passenger_id);


--
-- TOC entry 4929 (class 1259 OID 16831)
-- Name: idx_booking_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_booking_status ON public.booking USING btree (status);


--
-- TOC entry 4918 (class 1259 OID 16826)
-- Name: idx_flight_arr_airport; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_flight_arr_airport ON public.flight USING btree (arr_airport_id);


--
-- TOC entry 4919 (class 1259 OID 16825)
-- Name: idx_flight_dep_airport; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_flight_dep_airport ON public.flight USING btree (dep_airport_id);


--
-- TOC entry 4920 (class 1259 OID 16828)
-- Name: idx_flight_dep_time; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_flight_dep_time ON public.flight USING btree (dep_time);


--
-- TOC entry 4921 (class 1259 OID 16829)
-- Name: idx_flight_flight_number; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_flight_flight_number ON public.flight USING btree (flight_number);


--
-- TOC entry 4922 (class 1259 OID 16827)
-- Name: idx_flight_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_flight_status ON public.flight USING btree (flight_status_id);


--
-- TOC entry 4968 (class 2620 OID 16824)
-- Name: flight trg_approve_charter_request; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_approve_charter_request AFTER INSERT ON public.flight FOR EACH ROW EXECUTE FUNCTION public.trg_approve_charter_request_on_flight_insert();


--
-- TOC entry 4969 (class 2620 OID 16812)
-- Name: flight trg_check_airplane_availability; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_check_airplane_availability BEFORE INSERT OR UPDATE ON public.flight FOR EACH ROW EXECUTE FUNCTION public.check_airplane_availability();


--
-- TOC entry 4972 (class 2620 OID 16822)
-- Name: booking_passenger trg_check_capacity; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_check_capacity BEFORE INSERT OR UPDATE ON public.booking_passenger FOR EACH ROW EXECUTE FUNCTION public.trg_check_airplane_capacity();


--
-- TOC entry 4970 (class 2620 OID 16816)
-- Name: crew trg_check_crew_availability; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_check_crew_availability BEFORE INSERT OR UPDATE ON public.crew FOR EACH ROW EXECUTE FUNCTION public.check_crew_availability();


--
-- TOC entry 4971 (class 2620 OID 16818)
-- Name: crew trg_prevent_duplicate_crew_member; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_prevent_duplicate_crew_member BEFORE INSERT ON public.crew FOR EACH ROW EXECUTE FUNCTION public.prevent_duplicate_crew_member();


--
-- TOC entry 4973 (class 2620 OID 16820)
-- Name: booking_passenger trg_unique_seat; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_unique_seat BEFORE INSERT OR UPDATE ON public.booking_passenger FOR EACH ROW EXECUTE FUNCTION public.trg_check_unique_seat();


--
-- TOC entry 4944 (class 2606 OID 16477)
-- Name: airline airline_airport_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airline
    ADD CONSTRAINT airline_airport_id_fkey FOREIGN KEY (airport_id) REFERENCES public.airport(id);


--
-- TOC entry 4945 (class 2606 OID 16482)
-- Name: airline airline_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airline
    ADD CONSTRAINT airline_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.user_account(id);


--
-- TOC entry 4948 (class 2606 OID 16536)
-- Name: airplane_airline airplane_airline_airline_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airplane_airline
    ADD CONSTRAINT airplane_airline_airline_id_fkey FOREIGN KEY (airline_id) REFERENCES public.airline(id);


--
-- TOC entry 4949 (class 2606 OID 16531)
-- Name: airplane_airline airplane_airline_airplane_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.airplane_airline
    ADD CONSTRAINT airplane_airline_airplane_id_fkey FOREIGN KEY (airplane_id) REFERENCES public.airplane(id);


--
-- TOC entry 4959 (class 2606 OID 16612)
-- Name: booking booking_flight_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking
    ADD CONSTRAINT booking_flight_id_fkey FOREIGN KEY (flight_id) REFERENCES public.flight(id);


--
-- TOC entry 4960 (class 2606 OID 16623)
-- Name: booking_passenger booking_passenger_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_passenger
    ADD CONSTRAINT booking_passenger_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.booking(id);


--
-- TOC entry 4961 (class 2606 OID 16633)
-- Name: booking_passenger booking_passenger_seat_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_passenger
    ADD CONSTRAINT booking_passenger_seat_id_fkey FOREIGN KEY (seat_id) REFERENCES public.seat(id);


--
-- TOC entry 4966 (class 2606 OID 16665)
-- Name: charter_contact charter_contact_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_contact
    ADD CONSTRAINT charter_contact_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.charter_request(id);


--
-- TOC entry 4962 (class 2606 OID 16654)
-- Name: charter_request charter_request_airline_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_request
    ADD CONSTRAINT charter_request_airline_id_fkey FOREIGN KEY (airline_id) REFERENCES public.airline(id);


--
-- TOC entry 4963 (class 2606 OID 16762)
-- Name: charter_request charter_request_arrival_airport_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_request
    ADD CONSTRAINT charter_request_arrival_airport_id_fkey FOREIGN KEY (arrival_airport_id) REFERENCES public.airport(id) NOT VALID;


--
-- TOC entry 4964 (class 2606 OID 16774)
-- Name: charter_request charter_request_departure_airport_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_request
    ADD CONSTRAINT charter_request_departure_airport_id_fkey FOREIGN KEY (departure_airport_id) REFERENCES public.airport(id) NOT VALID;


--
-- TOC entry 4965 (class 2606 OID 16649)
-- Name: charter_request charter_request_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.charter_request
    ADD CONSTRAINT charter_request_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.user_account(id);


--
-- TOC entry 4967 (class 2606 OID 16741)
-- Name: connect connects_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.connect
    ADD CONSTRAINT connects_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.user_account(id) ON DELETE CASCADE;


--
-- TOC entry 4957 (class 2606 OID 16593)
-- Name: crew crew_flight_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.crew
    ADD CONSTRAINT crew_flight_id_fkey FOREIGN KEY (flight_id) REFERENCES public.flight(id);


--
-- TOC entry 4958 (class 2606 OID 16598)
-- Name: crew crew_worker_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.crew
    ADD CONSTRAINT crew_worker_id_fkey FOREIGN KEY (worker_id) REFERENCES public.worker_details(id);


--
-- TOC entry 4951 (class 2606 OID 16751)
-- Name: flight flight_airplane_airline_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_airplane_airline_fkey FOREIGN KEY (airplane_airline_id) REFERENCES public.airplane_airline(id) NOT VALID;


--
-- TOC entry 4952 (class 2606 OID 16572)
-- Name: flight flight_arr_airport_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_arr_airport_id_fkey FOREIGN KEY (arr_airport_id) REFERENCES public.airport(id);


--
-- TOC entry 4953 (class 2606 OID 16790)
-- Name: flight flight_charter_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_charter_request_id_fkey FOREIGN KEY (charter_request_id) REFERENCES public.charter_request(id) NOT VALID;


--
-- TOC entry 4954 (class 2606 OID 16567)
-- Name: flight flight_dep_airport_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_dep_airport_id_fkey FOREIGN KEY (dep_airport_id) REFERENCES public.airport(id);


--
-- TOC entry 4955 (class 2606 OID 16577)
-- Name: flight flight_flight_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_flight_status_id_fkey FOREIGN KEY (flight_status_id) REFERENCES public.flight_status(id);


--
-- TOC entry 4956 (class 2606 OID 16802)
-- Name: flight flight_worker_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.flight
    ADD CONSTRAINT flight_worker_id_fkey FOREIGN KEY (worker_id) REFERENCES public.user_account(id) NOT VALID;


--
-- TOC entry 4950 (class 2606 OID 16549)
-- Name: seat seat_airplane_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat
    ADD CONSTRAINT seat_airplane_id_fkey FOREIGN KEY (airplane_id) REFERENCES public.airplane(id);


--
-- TOC entry 4942 (class 2606 OID 16414)
-- Name: user_account user_account_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_account
    ADD CONSTRAINT user_account_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.role(id);


--
-- TOC entry 4943 (class 2606 OID 16430)
-- Name: user_passenger user_passenger_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_passenger
    ADD CONSTRAINT user_passenger_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.user_account(id);


--
-- TOC entry 4946 (class 2606 OID 16505)
-- Name: worker_details worker_details_airline_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_details
    ADD CONSTRAINT worker_details_airline_id_fkey FOREIGN KEY (airline_id) REFERENCES public.airline(id);


--
-- TOC entry 4947 (class 2606 OID 16495)
-- Name: worker_details worker_details_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker_details
    ADD CONSTRAINT worker_details_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.user_account(id);


-- Completed on 2025-06-23 09:45:28

--
-- PostgreSQL database dump complete
--

