CREATE DATABASE population_survey;
USE population_survey;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE surveys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    division VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender ENUM('m', 'f') NOT NULL,
    birthday DATE NOT NULL,
    occupation ENUM('employed', 'unemployed', 'student', 'retired', 'homemaker') NOT NULL,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);



-- First, let's create arrays of sample values
SET @divisions = 'Dhaka,Chittagong,Rajshahi,Khulna,Barisal,Sylhet,Rangpur,Mymensingh';
SET @districts = 'Dhaka,Gazipur,Narayanganj,Chittagong,Cox\'s Bazar,Rajshahi,Khulna,Jessore,Barisal,Sylhet,Rangpur,Mymensingh';
SET @streets = 'Main Street,Park Road,Lake View,Station Road,College Road,Market Street,River View,Hill Road,Garden Avenue,Port Road';
SET @names = 'Rahim,Karim,Jahan,Ahmed,Hassan,Hossain,Begum,Islam,Rahman,Ali,Khatun,Akter,Miah,Uddin,Khan,Siddique,Rashid,Matin,Reza,Sultana';

-- Generate 100 survey entries
DELIMITER //
CREATE PROCEDURE generate_dummy_data()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE j INT;
    DECLARE division_name VARCHAR(100);
    DECLARE district_name VARCHAR(100);
    DECLARE street_name VARCHAR(100);
    DECLARE full_address TEXT;
    DECLARE survey_id INT;
    
    -- Generate surveys
    WHILE i <= 100 DO
        -- Get random division
        SET division_name = ELT(1 + FLOOR(RAND() * 8), 'Dhaka', 'Chittagong', 'Rajshahi', 'Khulna', 'Barisal', 'Sylhet', 'Rangpur', 'Mymensingh');
        
        -- Get random district (matching division context)
        CASE division_name
            WHEN 'Dhaka' THEN SET district_name = ELT(1 + FLOOR(RAND() * 3), 'Dhaka', 'Gazipur', 'Narayanganj');
            WHEN 'Chittagong' THEN SET district_name = ELT(1 + FLOOR(RAND() * 2), 'Chittagong', 'Cox\'s Bazar');
            ELSE SET district_name = division_name;
        END CASE;
        
        -- Get random street
        SET street_name = ELT(1 + FLOOR(RAND() * 10), 'Main Street', 'Park Road', 'Lake View', 'Station Road', 'College Road', 
                             'Market Street', 'River View', 'Hill Road', 'Garden Avenue', 'Port Road');
        
        -- Generate random house number and create full address
        SET full_address = CONCAT(FLOOR(RAND() * 100 + 1), ', ', street_name, ', ', district_name);
        
        -- Insert survey
        INSERT INTO surveys (division, district, address) 
        VALUES (division_name, district_name, full_address);
        
        -- Get the inserted survey ID
        SET survey_id = LAST_INSERT_ID();
        
        -- Generate random number of members (2-6) for this survey
        SET j = 1;
        WHILE j <= 2 + FLOOR(RAND() * 5) DO
            -- Insert member with random name and birthdate
            INSERT INTO members (survey_id, name, gender, birthday, occupation)
            VALUES (
                survey_id,
                ELT(1 + FLOOR(RAND() * 20), 
                    'Rahim', 'Karim', 'Jahan', 'Ahmed', 'Hassan', 
                    'Hossain', 'Begum', 'Islam', 'Rahman', 'Ali',
                    'Khatun', 'Akter', 'Miah', 'Uddin', 'Khan',
                    'Siddique', 'Rashid', 'Matin', 'Reza', 'Sultana'),
                -- gender
                ELT(1 + FLOOR(RAND() * 2), 'm', 'f'),
                DATE_SUB(CURRENT_DATE, INTERVAL FLOOR(RAND() * 30000) DAY),
                -- occupation
                ELT(1 + FLOOR(RAND() * 5), 'employed', 'unemployed', 'student', 'retired', 'homemaker')
            );
            SET j = j + 1;
        END WHILE;
        
        SET i = i + 1;
    END WHILE;
END //
DELIMITER ;

-- Execute the procedure to generate data
CALL generate_dummy_data();

-- Clean up
DROP PROCEDURE generate_dummy_data;