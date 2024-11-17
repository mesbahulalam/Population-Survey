CREATE DATABASE population_survey;
USE population_survey;

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
    birthday DATE NOT NULL,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);
