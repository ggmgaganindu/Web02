CREATE DATABASE IF NOT EXISTS nsbm_marketplace;
USE nsbm_marketplace;

-- Disable foreign key checks to allow drops/creates cleanly
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products & Services table (with lesson_playlist JSON column)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    department ENUM('Computing', 'Engineering', 'Business', 'General') DEFAULT 'General',
    university ENUM('Plymouth', 'UGC/VU', 'General') DEFAULT 'General',
    title VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    video_url VARCHAR(255) DEFAULT NULL,
    lesson_playlist TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Simulated Purchases/Orders table (with Payment Options columns)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('card', 'bank_transfer', 'cash_on_campus') DEFAULT 'card',
    payment_status ENUM('paid', 'pending', 'completed') DEFAULT 'completed',
    delivery_location VARCHAR(255) DEFAULT NULL,
    contact_phone VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Seed Data for Users
INSERT INTO users (id, username, email, password, role, status) VALUES
(1, 'admin', 'admin@nsbm.ac.lk', '$2y$10$7ejpMkJV.sZIL5qSATdBGeEvT2dz5jNowE4by67by9MlQdXXMHKe.', 'admin', 'active'),
(2, 'john', 'john@nsbm.ac.lk', '$2y$10$ttN3kEnid4T8iot6UYI5mOXAN6tjmGH6i0vQIl2fq7LFBVjDRPteW', 'student', 'active'),
(3, 'jane', 'jane@nsbm.ac.lk', '$2y$10$ttN3kEnid4T8iot6UYI5mOXAN6tjmGH6i0vQIl2fq7LFBVjDRPteW', 'student', 'active');

-- Insert Seed Data for Categories
INSERT INTO categories (id, name, description) VALUES
(1, 'Academic Resources', 'Textbooks, study guides, past exams, and lecture notes.'),
(2, 'Electronics & Gadgets', 'Calculators, laptop accessories, chargers, and tech support.'),
(3, 'Food & Beverages', 'Homemade snacks, sweet treats, custom lunch boxes, and drinks.'),
(4, 'Fashion & Accessories', 'Custom NSBM t-shirts, tote bags, and handcraft jewelry.'),
(5, 'Student Services', 'Study Video Packs, tutoring, photography, printing, graphic design, and coding assistance.');

-- Insert Seed Data for Products
INSERT INTO products (id, seller_id, category_id, department, university, title, description, price, image_path, video_url, lesson_playlist, status) VALUES
(1, 2, 1, 'Engineering', 'General', 'Engineering Mathematics II Textbook', 'Gently used Engineering Mathematics II textbook. Essential for first-year computing and engineering students. No highlighting or ripped pages.', 1500.00, 'math_textbook.png', NULL, NULL, 'approved'),
(2, 2, 2, 'General', 'General', 'Casio fx-991ES Plus Scientific Calculator', 'Fully functional scientific calculator. Perfect for engineering, computing, and business students. Comes with sliding cover.', 2500.00, 'calculator.png', NULL, NULL, 'approved'),
(3, 3, 3, 'General', 'General', 'Freshly Baked Chocolate Chip Cookies', 'Delicious homemade chocolate chip cookies, baked fresh daily. Box of 6 cookies. Order at least 1 day in advance!', 600.00, 'cookies.png', NULL, NULL, 'approved'),
(4, 3, 5, 'Computing', 'General', 'Python & Java Programming Tutoring', 'One-on-one tutoring sessions for object-oriented programming. Perfect for students struggling with OOP or data structures courses. Rate per hour.', 1200.00, 'tutoring.png', NULL, NULL, 'approved'),
(5, 2, 4, 'General', 'General', 'Custom NSBM Tote Bag - Eco Friendly', 'Durable, hand-painted eco-friendly canvas tote bag with custom NSBM motifs. Excellent for carrying notebooks and tablets.', 850.00, 'totebag.png', NULL, NULL, 'approved'),

-- Study Video Packs under Student Services (with lesson_playlist JSON modules)
(6, 2, 5, 'Computing', 'Plymouth', 'Python OOP & Algorithms Video Masterclass - Plymouth Computing', '15-hour high definition video lecture pack covering Plymouth Software Engineering modules: Object Oriented Programming, Data Structures, and past paper walkthroughs.', 1800.00, 'computing_video.png', 'https://www.youtube.com/embed/gfkTfcpWqAY', '[{"title":"Lesson 1: Introduction to OOP & Python Classes","url":"https://www.youtube.com/embed/gfkTfcpWqAY","duration":"45 mins"},{"title":"Lesson 2: Inheritance, Polymorphism & Design Patterns","url":"https://www.youtube.com/embed/HXV3zeQKqGY","duration":"1 hr 10 mins"},{"title":"Lesson 3: Plymouth Past Exam Solution Walkthrough","url":"https://www.youtube.com/embed/1v0mK5Z4_5M","duration":"55 mins"}]', 'approved'),
(7, 3, 5, 'Computing', 'UGC/VU', 'Database Systems & SQL Optimization Video Pack - UGC/VU Computing', 'Complete video series explaining ER Diagram design, relational database normalization, SQL queries, and VU lab exam step-by-step guides.', 1600.00, 'computing_video.png', 'https://www.youtube.com/embed/HXV3zeQKqGY', '[{"title":"Lesson 1: ER Diagrams & Relational Mapping","url":"https://www.youtube.com/embed/HXV3zeQKqGY","duration":"50 mins"},{"title":"Lesson 2: Complex SQL Queries & Index Optimization","url":"https://www.youtube.com/embed/gfkTfcpWqAY","duration":"1 hr 05 mins"},{"title":"Lesson 3: VU Database Lab Exam Walkthrough","url":"https://www.youtube.com/embed/n3E937xvv3g","duration":"40 mins"}]', 'approved'),
(8, 2, 5, 'Engineering', 'Plymouth', 'Engineering Statics & Dynamics Video Tutorials - Plymouth Engineering', '12 video modules covering Plymouth Mechanical & Civil Engineering mechanics, vector statics, stress analysis, and exam calculation tricks.', 2200.00, 'engineering_video.png', 'https://www.youtube.com/embed/1v0mK5Z4_5M', '[{"title":"Lesson 1: Vector Mechanics & Equilibrium","url":"https://www.youtube.com/embed/1v0mK5Z4_5M","duration":"1 hr 15 mins"},{"title":"Lesson 2: Truss Analysis & Internal Forces","url":"https://www.youtube.com/embed/n3E937xvv3g","duration":"50 mins"},{"title":"Lesson 3: Plymouth Past Exam Calculation Review","url":"https://www.youtube.com/embed/yW_R9QWvY3U","duration":"45 mins"}]', 'approved'),
(9, 3, 5, 'Engineering', 'UGC/VU', 'Electrical Circuit Analysis & Microcontrollers Video Series - UGC/VU Engineering', 'Comprehensive video guide for UGC/VU Mechatronics & Electrical Engineering students. Covers AC/DC circuits, Kirchhoff laws, and Arduino lab walkthroughs.', 2000.00, 'engineering_video.png', 'https://www.youtube.com/embed/n3E937xvv3g', '[{"title":"Lesson 1: Kirchhoff Laws & Nodal Analysis","url":"https://www.youtube.com/embed/n3E937xvv3g","duration":"40 mins"},{"title":"Lesson 2: AC Circuit Impedance & Phasors","url":"https://www.youtube.com/embed/1v0mK5Z4_5M","duration":"55 mins"},{"title":"Lesson 3: Arduino Microcontroller Lab Guide","url":"https://www.youtube.com/embed/nU2T1QZ3tG8","duration":"1 hr 00 mins"}]', 'approved'),
(10, 3, 5, 'Business', 'Plymouth', 'Financial Accounting & Managerial Analytics Video Pack - Plymouth Business', 'Plymouth Business School complete video course covering financial statement analysis, balance sheets, cash flow forecasting, and Excel modeling.', 1750.00, 'business_video.png', 'https://www.youtube.com/embed/yW_R9QWvY3U', '[{"title":"Lesson 1: Income Statements & Balance Sheets","url":"https://www.youtube.com/embed/yW_R9QWvY3U","duration":"50 mins"},{"title":"Lesson 2: Cash Flow Forecasting & Excel Ratios","url":"https://www.youtube.com/embed/nU2T1QZ3tG8","duration":"1 hr 10 mins"},{"title":"Lesson 3: Plymouth Business Analytics Exam Prep","url":"https://www.youtube.com/embed/gfkTfcpWqAY","duration":"45 mins"}]', 'approved'),
(11, 2, 5, 'Business', 'UGC/VU', 'Principles of Marketing & Strategic Management Video Series - UGC/VU Business', 'UGC/VU Business Administration video revision pack including market segmentation strategies, SWOT analysis, consumer behavior, and case study solutions.', 1500.00, 'business_video.png', 'https://www.youtube.com/embed/nU2T1QZ3tG8', '[{"title":"Lesson 1: Market Segmentation & 4Ps Strategy","url":"https://www.youtube.com/embed/nU2T1QZ3tG8","duration":"45 mins"},{"title":"Lesson 2: Consumer Behavior & SWOT Matrix","url":"https://www.youtube.com/embed/yW_R9QWvY3U","duration":"50 mins"},{"title":"Lesson 3: UGC/VU Strategic Management Case Study","url":"https://www.youtube.com/embed/HXV3zeQKqGY","duration":"1 hr 00 mins"}]', 'approved');

-- Seed Sample Order for Student John (Id 2) to test instant video watching
INSERT INTO orders (id, buyer_id, total_amount, payment_method, payment_status, delivery_location, contact_phone) VALUES
(1, 2, 1800.00, 'card', 'paid', 'Online Digital Access', '0771234567');

INSERT INTO order_items (id, order_id, product_id, price, quantity) VALUES
(1, 1, 6, 1800.00, 1);
