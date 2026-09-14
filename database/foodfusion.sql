-- FoodFusion database for MySQL / MariaDB
-- Import from the project directory: mysql -u root < database/foodfusion.sql

CREATE DATABASE IF NOT EXISTS foodfusion
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE foodfusion;

-- Stores registered members and the login lockout information.
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Main recipe collection. A recipe can belong to one member.
CREATE TABLE IF NOT EXISTS recipes (
    recipe_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    ingredients TEXT NOT NULL,
    instructions TEXT NOT NULL,
    cuisine_type VARCHAR(60) NOT NULL,
    dietary_preference VARCHAR(50) NOT NULL DEFAULT 'None',
    difficulty ENUM('Easy', 'Medium', 'Hard') NOT NULL DEFAULT 'Easy',
    image_path VARCHAR(500) NULL,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recipes_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_recipe_filters (cuisine_type, dietary_preference, difficulty),
    INDEX idx_recipe_status (status, is_featured)
) ENGINE=InnoDB;

-- Comments written below recipes.
CREATE TABLE IF NOT EXISTS comments (
    comment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    comment_text TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comments_recipe
        FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_comments_recipe (recipe_id, created_at)
) ENGINE=InnoDB;

-- Tracks views, likes and saves as required by the assignment.
CREATE TABLE IF NOT EXISTS interactions (
    interaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    interaction_type ENUM('view', 'like', 'save') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_interactions_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_interactions_recipe
        FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_interaction_recipe (recipe_id, interaction_type),
    INDEX idx_interaction_user (user_id, created_at)
) ENGINE=InnoDB;

-- Community members can share a recipe, tip or experience.
CREATE TABLE IF NOT EXISTS community_posts (
    post_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    post_type ENUM('recipe', 'tip', 'experience') NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(500) NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_community_posts_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_community_status (status, created_at)
) ENGINE=InnoDB;

-- One member can like each approved community post once.
CREATE TABLE IF NOT EXISTS community_post_likes (
    like_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_post_likes_post
        FOREIGN KEY (post_id) REFERENCES community_posts(post_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_post_likes_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY unique_post_like (post_id, user_id),
    INDEX idx_post_likes_user (user_id)
) ENGINE=InnoDB;

-- Comments written on approved community posts.
CREATE TABLE IF NOT EXISTS community_post_comments (
    comment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    comment_text TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_post_comments_post
        FOREIGN KEY (post_id) REFERENCES community_posts(post_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_post_comments_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_post_comments_post (post_id, created_at),
    INDEX idx_post_comments_user (user_id)
) ENGINE=InnoDB;

-- Homepage news feed entries.
CREATE TABLE IF NOT EXISTS news_posts (
    news_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(500) NULL,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_news_posts_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_news_published (published_at)
) ENGINE=InnoDB;

-- Cooking events shown in the homepage carousel.
CREATE TABLE IF NOT EXISTS events (
    event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    location VARCHAR(150) NOT NULL,
    image_path VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB;

-- Messages may be sent by visitors, so user_id is optional.
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_messages_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;

-- Both culinary and educational downloads/videos use one resource table.
CREATE TABLE IF NOT EXISTS resources (
    resource_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    resource_category ENUM('culinary', 'educational') NOT NULL,
    resource_type ENUM('recipe_card', 'tutorial', 'video', 'infographic', 'guide', 'article') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resources_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_resource_filter (resource_category, resource_type)
) ENGINE=InnoDB;

-- This also updates an existing imported database when the article type is added later.
ALTER TABLE resources
    MODIFY resource_type ENUM('recipe_card', 'tutorial', 'video', 'infographic', 'guide', 'article') NOT NULL;

-- Demo users. Passwords were created with PHP password_hash().
-- admin@foodfusion.test  / Admin123!
-- member@foodfusion.test / Member123!
INSERT IGNORE INTO users
    (user_id, first_name, last_name, email, password_hash, role)
VALUES
    (1, 'FoodFusion', 'Admin', 'admin@foodfusion.test',
     '$2y$10$bEKpyiTxjgPvrhjS7xHlmu0pkskFXG/KAWHL6gf2aTAh.8i766Zve', 'admin'),
    (2, 'Maya', 'Chen', 'member@foodfusion.test',
     '$2y$10$6ufxNxkx0GbX9k.vnTyMu.H2PDFQk1cxTR.7PEZHx6yH15Nl5FxVu', 'member');

-- Sample recipes. image_path contains online image URLs, not generated files.
INSERT IGNORE INTO recipes
    (recipe_id, user_id, title, description, ingredients, instructions,
     cuisine_type, dietary_preference, difficulty, image_path, is_featured, status)
VALUES
    (1, 1, 'Garden Herb Pasta',
     'A fresh pasta dish with mushrooms, spinach and herbs.',
     '250 g pasta\n150 g mushrooms\n2 cups spinach\n2 cloves garlic\nFresh herbs\nOlive oil',
     '1. Boil the pasta.\n2. Saute the mushrooms and garlic.\n3. Add spinach.\n4. Toss with pasta and herbs.',
     'Italian', 'Vegetarian', 'Easy',
     'https://images.unsplash.com/photo-1473093226795-af9932fe5856?auto=format&fit=crop&w=1200&q=80',
     TRUE, 'published'),
    (2, 2, 'Homemade Vegetable Pizza',
     'A colourful homemade pizza with a crisp base and fresh vegetables.',
     '1 pizza base\n150 g tomato sauce\n150 g mozzarella\nMixed peppers\nMushrooms\nFresh basil',
     '1. Heat the oven to 220 C.\n2. Add sauce and toppings.\n3. Bake for 12-15 minutes.\n4. Finish with basil.',
     'Italian', 'Vegetarian', 'Medium',
     'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=1200&q=80',
     TRUE, 'published'),
    (3, 1, 'Thai Green Curry',
     'A fragrant coconut curry filled with vegetables.',
     '2 tbsp green curry paste\n400 ml coconut milk\nMixed vegetables\nFresh basil\nCooked rice',
     '1. Fry the curry paste.\n2. Stir in coconut milk.\n3. Add vegetables and simmer.\n4. Serve with rice.',
     'Thai', 'Vegan', 'Medium',
     'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published'),
    (4, 2, 'Rainbow Avocado Salad',
     'A quick, crisp salad with avocado and a lemon dressing.',
     'Mixed leaves\n1 avocado\nCherry tomatoes\nCucumber\nLemon juice\nOlive oil',
     '1. Wash and chop the vegetables.\n2. Whisk lemon juice and oil.\n3. Toss everything together and serve.',
     'International', 'Vegan', 'Easy',
     'https://images.unsplash.com/photo-1546793665-c74683f339c1?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published');

INSERT IGNORE INTO comments
    (comment_id, user_id, recipe_id, comment_text)
VALUES
    (1, 2, 1, 'Simple instructions and a really fresh flavour.'),
    (2, 1, 2, 'A good recipe for a weekend cooking session.');

INSERT IGNORE INTO interactions
    (interaction_id, user_id, recipe_id, interaction_type)
VALUES
    (1, 2, 1, 'view'),
    (2, 2, 1, 'like'),
    (3, 2, 2, 'save');

INSERT IGNORE INTO community_posts
    (post_id, user_id, post_type, title, content, image_path, status)
VALUES
    (1, 2, 'tip', 'Keep herbs fresh for longer',
     'Wrap washed herbs in a slightly damp kitchen towel before refrigerating them.',
     'https://images.unsplash.com/photo-1530832842230-87253f48d74f?auto=format&fit=crop&w=1200&q=80',
     'approved'),
    (2, 2, 'experience', 'My first homemade pizza',
     'Making the dough by hand took patience, but the crisp result was worth it.',
     'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=1200&q=80',
     'approved'),
    (3, 2, 'tip', 'Rest dough before shaping',
     'Let bread or pizza dough rest so it becomes softer and easier to shape.',
     'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=1200&q=80',
     'pending');

INSERT IGNORE INTO community_post_likes
    (like_id, post_id, user_id)
VALUES
    (1, 1, 1),
    (2, 2, 1);

INSERT IGNORE INTO community_post_comments
    (comment_id, post_id, user_id, comment_text)
VALUES
    (1, 1, 1, 'A useful tip for reducing food waste. Thank you for sharing!'),
    (2, 2, 1, 'Homemade pizza is a great way to practise working with dough.');

INSERT IGNORE INTO news_posts
    (news_id, user_id, title, description, image_path, published_at)
VALUES
    (1, 1, 'Seasonal cooking: make the most of fresh produce',
     'Simple ideas for choosing seasonal ingredients and reducing food waste.',
     'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80',
     CURRENT_TIMESTAMP),
    (2, 1, 'Featured recipe: Garden Herb Pasta',
     'This week we celebrate an easy vegetarian dinner shared by our community.',
     'https://images.unsplash.com/photo-1473093226795-af9932fe5856?auto=format&fit=crop&w=1200&q=80',
     CURRENT_TIMESTAMP);

INSERT IGNORE INTO events
    (event_id, user_id, title, description, event_date, location, image_path)
VALUES
    (1, 1, 'Pasta from Scratch Workshop',
     'Learn how to mix, knead and shape fresh pasta with our community cooks.',
     DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 14 DAY), 'FoodFusion Community Kitchen',
     'https://images.unsplash.com/photo-1556761223-4c4282c73f77?auto=format&fit=crop&w=1200&q=80'),
    (2, 1, 'Weekend Baking Club',
     'A friendly practical session covering bread dough and simple pastries.',
     DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY), 'Riverside Learning Centre',
     'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=1200&q=80');

INSERT IGNORE INTO contact_messages
    (message_id, user_id, name, email, subject, message)
VALUES
    (1, 2, 'Maya Chen', 'member@foodfusion.test',
     'Recipe request', 'Could you add a beginner-friendly dumpling recipe?');

INSERT IGNORE INTO resources
    (resource_id, user_id, title, description, resource_category,
     resource_type, file_path, thumbnail_path)
VALUES
    (1, 1, 'Garden Herb Pasta Recipe Card',
     'A printable recipe card for the featured pasta dish.',
     'culinary', 'recipe_card', 'output/pdf/garden-herb-pasta-recipe-card.pdf',
     'https://images.unsplash.com/photo-1473093226795-af9932fe5856?auto=format&fit=crop&w=1200&q=80'),
    (2, 1, 'Basic Knife Skills',
     'A short tutorial covering safe slicing, dicing and chopping.',
     'culinary', 'video', 'https://www.youtube.com/watch?v=Ydc_SaQ_eRQ',
     'https://images.unsplash.com/photo-1593618998160-e34014e67546?auto=format&fit=crop&w=1200&q=80'),
    (3, 1, 'A Beginner Guide to Solar Energy',
     'An introductory educational guide to how solar panels create electricity.',
     'educational', 'guide', 'output/pdf/solar-energy-guide.pdf',
     'https://images.unsplash.com/photo-1508514177221-188b1cf16e9d?auto=format&fit=crop&w=1200&q=80'),
    (4, 1, 'Wind Power Explained',
     'A visual introduction to wind turbines and renewable electricity.',
     'educational', 'infographic', 'output/pdf/wind-power-infographic.pdf',
     'https://images.unsplash.com/photo-1466611653911-95081537e5b7?auto=format&fit=crop&w=1200&q=80');

INSERT IGNORE INTO resources
    (resource_id, user_id, title, description, resource_category,
     resource_type, file_path, thumbnail_path)
VALUES
    (5, 1, 'Five Useful Kitchen Hacks',
     'A printable guide covering preparation, chopping, storage and food-waste tips.',
     'culinary', 'guide', 'output/pdf/kitchen-hacks-guide.pdf',
     'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=80'),
    (6, 1, 'Renewable Energy 101',
     'A short National Geographic video introducing solar, wind and other renewable sources.',
     'educational', 'video', 'https://www.youtube.com/watch?v=1kUE0BZtTRc',
     'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?auto=format&fit=crop&w=1200&q=80'),
    (7, 1, 'Solar Energy Basics',
     'An introductory article from the U.S. Department of Energy about solar technologies.',
     'educational', 'article', 'https://www.energy.gov/topics/solar-energy',
     'https://images.unsplash.com/photo-1509391366360-2e959784a276?auto=format&fit=crop&w=1200&q=80'),
    (8, 1, 'How Wind Turbines Work',
     'A learning article explaining how wind movement is converted into electricity.',
     'educational', 'article', 'https://www.energy.gov/cmei/systems/how-do-wind-turbines-work',
     'https://images.unsplash.com/photo-1532601224476-15c79f2f7a51?auto=format&fit=crop&w=1200&q=80'),
    (9, 1, 'Learn to Bake',
     'A step-by-step collection of beginner baking lessons, techniques and recipe walk-throughs.',
     'culinary', 'tutorial', 'https://www.kingarthurbaking.com/learn',
     'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=1200&q=80');

-- Keep file paths correct if this script is imported again over an older copy.
UPDATE resources SET file_path = 'output/pdf/garden-herb-pasta-recipe-card.pdf' WHERE resource_id = 1;
UPDATE resources SET file_path = 'output/pdf/solar-energy-guide.pdf' WHERE resource_id = 3;
UPDATE resources SET file_path = 'output/pdf/wind-power-infographic.pdf' WHERE resource_id = 4;
