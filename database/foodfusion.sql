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
    reply_text TEXT NULL,
    replied_at DATETIME NULL,
    replied_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_messages_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_contact_replied_by
        FOREIGN KEY (replied_by) REFERENCES users(user_id)
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

-- Sample recipes. image_path contains online image URLs or local upload paths.
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
     FALSE, 'published'),
    (5, 1, 'Traditional Mohinga (မုန့်ဟင်းခါး)',
     'The beloved national dish of Myanmar: delicate rice vermicelli in a rich, lemongrass-infused catfish and chickpea broth with crispy fritters.',
     '300g rice vermicelli\n500g catfish or white fish\n3 lemongrass stalks\n1 large onion\n5 cloves garlic\n1 tbsp minced ginger\n3 tbsp toasted chickpea flour\n2 tbsp fish sauce\n1 tsp turmeric\nHard-boiled eggs\nCrispy chickpea fritters\nFresh coriander and lime',
     '1. Simmer fish with bruised lemongrass and turmeric; flake meat and reserve stock.\n2. Saute pureed onion, garlic, and ginger until aromatic.\n3. Add flaked fish, stock, and chickpea flour dissolved in water.\n4. Simmer gently for 25 minutes until broth is rich and golden.\n5. Assemble rice vermicelli in bowls, ladle broth over, and garnish with egg, crispy fritters, coriander, and lime.',
     'Burmese', 'Dairy-Free', 'Medium',
     'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=1200&q=80',
     TRUE, 'published'),
    (6, 1, 'Shan Noodles (ရှမ်းခေါက်ဆွဲ)',
     'A comforting Shan classic of soft rice noodles tossed in a savoury spiced chicken-tomato sauce with roasted peanuts and crispy garlic oil.',
     '300g flat Shan rice noodles\n250g minced chicken\n3 ripe tomatoes, diced\n1 tbsp fermented soy bean paste\n4 cloves garlic, minced\n1 tsp sweet paprika\n2 tbsp roasted crushed peanuts\n2 tbsp crispy garlic oil\nSpring onions\nPickled mustard greens',
     '1. Heat oil in a pan, fry garlic and paprika, then simmer diced tomatoes and bean paste into a rich gravy.\n2. Add minced chicken and cook on medium heat for 12 minutes.\n3. Blanch Shan rice noodles in boiling water for 2 minutes, then drain.\n4. Spoon the warm chicken-tomato gravy over the noodles.\n5. Top with roasted peanuts, spring onions, garlic oil, and serve with pickled mustard greens.',
     'Burmese', 'Gluten-Free', 'Easy',
     'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?auto=format&fit=crop&w=1200&q=80',
     TRUE, 'published'),
    (7, 2, 'Tofu Nway - Warm Shan Tofu (တို့ဟူးနွေး)',
     'Silky, custard-like warm yellow split pea tofu poured over tender rice noodles, drizzled with sweet soy sauce, chili oil, and toasted sesame.',
     '200g yellow split pea flour\n4 cups water\n1/2 tsp turmeric\n1/2 tsp salt\n250g rice vermicelli\n2 tbsp sweet dark soy sauce\n2 tbsp roasted chili oil\n2 tbsp crushed roasted peanuts\nCrispy fried shallots and coriander',
     '1. Whisk yellow split pea flour with water, turmeric, and salt until smooth.\n2. Cook over medium-low heat, stirring constantly until thick, glossy, and custard-like (about 15 minutes).\n3. Prepare warm rice vermicelli in bowls.\n4. Ladle steaming hot split-pea tofu generously over the noodles.\n5. Drizzle with sweet dark soy, chili oil, garlic crisps, toasted sesame seeds, and crushed peanuts.',
     'Burmese', 'Vegan', 'Medium',
     'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published'),
    (8, 2, 'Burmese Tea Leaf Salad - Laphet Thoke (လဖက်သုပ်)',
     'A dazzling medley of fermented green tea leaves, crunchy roasted beans, peanuts, toasted sesame, shredded cabbage, and fresh lime.',
     '3 tbsp fermented tea leaves (laphet)\n1/2 cup fried crunchy split peas and peanuts\n1 tbsp toasted sesame seeds\n1 cup finely shredded cabbage\n1 ripe tomato, sliced\n2 green chilies, sliced\n2 cloves garlic, sliced\n1 tbsp fresh lime juice\n2 tbsp peanut oil',
     '1. Mix fermented tea leaves with peanut oil, garlic, and fresh lime juice.\n2. Combine shredded cabbage, sliced tomatoes, and chilies in a mixing bowl.\n3. Add dressed tea leaves and toss thoroughly.\n4. Top with crunchy fried beans, roasted peanuts, and toasted sesame seeds immediately before serving.\n5. Enjoy as an energizing salad or traditional palate cleanser.',
     'Burmese', 'Vegetarian', 'Easy',
     'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published'),
    (9, 1, 'Burmese Coconut Chicken Noodles - Ohn No Khao Swé (အုန်းနို့ခေါက်ဆွဲ)',
     'Rich, velvety coconut milk and spiced chicken noodle soup served with boiled eggs, shallots, lime, and crispy wonton crisps.',
     '350g fresh egg noodles\n400g chicken thighs, diced\n400ml creamy coconut milk\n3 tbsp chickpea flour\n2 shallots, sliced\n4 cloves garlic\n1 tbsp ginger\n1 tsp turmeric\n1 tbsp fish sauce\nHard-boiled eggs, halved\nLime wedges and crispy wontons',
     '1. Blend shallots, garlic, and ginger; saute in oil with turmeric until fragrant.\n2. Add chicken pieces and saute until lightly browned.\n3. Dissolve chickpea flour in broth and stir in along with coconut milk and fish sauce.\n4. Simmer gently for 20 minutes until the soup is thick and aromatic.\n5. Place cooked noodles in deep bowls, ladle coconut soup over, and garnish with egg halves, lime, and crispy wontons.',
     'Burmese', 'None', 'Medium',
     'https://images.unsplash.com/photo-1552611052-33e04de081de?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published'),
    (10, 1, 'Japanese Chicken Teriyaki (照り焼きチキン)',
     'Pan-seared chicken thighs finished with a glossy reduction of soy sauce, mirin, sake, and ginger, served with steamed jasmine rice.',
     '400g chicken thighs\n3 tbsp soy sauce\n3 tbsp mirin\n2 tbsp sake or rice vinegar\n1 tbsp brown sugar\n1 tsp grated fresh ginger\n1 tbsp toasted sesame seeds\nSteamed broccoli and rice',
     '1. Sear chicken skin-side down in a hot pan until crisp and golden.\n2. Flip and cook through.\n3. Whisk soy sauce, mirin, sake, sugar, and ginger; pour into the pan.\n4. Simmer vigorously until sauce reduces into a thick lacquer coating the chicken.\n5. Slice and serve hot sprinkled with toasted sesame seeds.',
     'Japanese', 'Dairy-Free', 'Easy',
     'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published'),
    (11, 2, 'Classic Spanish Paella',
     'Traditional saffron-infused Bomba rice slowly simmered with plump prawns, bell peppers, green peas, garlic, and fresh lemons.',
     '300g Bomba rice\n8 large prawns\n1 red bell pepper, sliced\n1 cup green peas\n1 pinch saffron threads\n750ml seafood stock\n3 cloves garlic\n1 tsp smoked paprika\n3 tbsp olive oil\nLemon wedges',
     '1. Heat olive oil in a wide paella skillet and sear prawns; set aside.\n2. Saute sliced peppers and minced garlic, then stir in paprika and saffron.\n3. Add rice and toast 2 minutes.\n4. Pour in simmering stock, spread rice evenly, and cook undisturbed for 15 minutes.\n5. Arrange prawns and peas on top, let cook until liquid is absorbed and crispy crust forms on the bottom.',
     'Spanish', 'Dairy-Free', 'Hard',
     'https://images.unsplash.com/photo-1534080564583-6be75777b70a?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published'),
    (12, 1, 'Vietnamese Fresh Summer Rolls (Gỏi Cuốn)',
     'Crisp cucumber, fresh mint, vermicelli noodles, and tender shrimp wrapped in delicate rice paper, with a creamy hoisin-peanut dip.',
     '12 rice paper sheets\n150g cooked shrimp, halved\n100g cooked rice vermicelli\n1 cucumber, julienned\n1 carrot, julienned\nFresh mint and Thai basil leaves\n3 tbsp hoisin sauce\n2 tbsp peanut butter',
     '1. Dip rice paper in warm water for 5 seconds until pliable, then lay flat.\n2. Place lettuce, vermicelli, cucumber, carrot, and fresh herbs along bottom third.\n3. Lay shrimp halves cut-side down across the middle.\n4. Fold sides in and roll tightly from bottom to top.\n5. Whisk hoisin sauce with peanut butter and warm water for dipping.',
     'Vietnamese', 'Gluten-Free', 'Easy',
     'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published'),
    (13, 2, 'Creamy Wild Mushroom Risotto',
     'Silky arborio rice gently stirred with sauteed cremini and porcini mushrooms, finished with butter, white wine, and Parmigiano-Reggiano.',
     '250g Arborio rice\n200g mixed mushrooms, sliced\n1 small onion, finely diced\n2 cloves garlic\n100ml dry white wine\n800ml warm vegetable broth\n40g butter\n40g grated parmesan cheese\nFresh thyme and olive oil',
     '1. Saute mushrooms in olive oil with thyme until golden; set aside.\n2. Soften diced onion and garlic in butter, add rice, and toast 2 minutes.\n3. Pour in wine and simmer until absorbed.\n4. Add warm broth one ladle at a time, stirring gently as it absorbs.\n5. Fold in mushrooms, cold butter, and parmesan for a rich, creamy finish.',
     'Italian', 'Vegetarian', 'Medium',
     'https://images.unsplash.com/photo-1633964913295-ceb43826e7c9?auto=format&fit=crop&w=1200&q=80',
     FALSE, 'published');

INSERT IGNORE INTO comments
    (comment_id, user_id, recipe_id, comment_text)
VALUES
    (1, 2, 1, 'Simple instructions and a really fresh flavour.'),
    (2, 1, 2, 'A good recipe for a weekend cooking session.'),
    (3, 2, 5, 'The authentic taste of Yangon morning street breakfast! Delicious broth.'),
    (4, 1, 6, 'Shan noodles are so easy and comforting to make during weekdays.');

INSERT IGNORE INTO interactions
    (interaction_id, user_id, recipe_id, interaction_type)
VALUES
    (1, 2, 1, 'view'),
    (2, 2, 1, 'like'),
    (3, 2, 2, 'save'),
    (4, 2, 5, 'view'),
    (5, 2, 5, 'like'),
    (6, 1, 5, 'like'),
    (7, 2, 5, 'save'),
    (8, 2, 6, 'view'),
    (9, 2, 6, 'like'),
    (10, 1, 6, 'like'),
    (11, 2, 7, 'view'),
    (12, 2, 8, 'like');

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
    (2, 1, 'Featured recipe: Traditional Burmese Mohinga',
     'This week we celebrate the classic flavours of Myanmar shared by our community.',
     'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=1200&q=80',
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
     'Printable kitchen recipe card with step-by-step measurements and preparation instructions.',
     'culinary', 'recipe_card', 'output/pdf/garden-herb-pasta-recipe-card.pdf',
     'https://images.unsplash.com/photo-1473093226795-af9932fe5856?auto=format&fit=crop&w=1200&q=80'),
    (2, 1, 'Traditional Mohinga Guide & Recipe Card',
     'Printable culinary card covering authentic Burmese catfish broth preparation and crispy garnishes.',
     'culinary', 'recipe_card', 'output/pdf/mohinga-traditional-recipe-card.pdf',
     'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=1200&q=80'),
    (3, 1, 'Authentic Shan Rice Noodles Recipe Card',
     'Downloadable PDF card covering chicken-tomato sauce simmer, garlic oil, and Shan seasonings.',
     'culinary', 'recipe_card', 'output/pdf/shan-noodles-recipe-card.pdf',
     'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?auto=format&fit=crop&w=1200&q=80'),
    (4, 1, 'Essential Knife Skills & Chopping Masterclass',
     'High-definition tutorial video demonstrating the pinch grip, guiding claw, and uniform vegetable cutting.',
     'culinary', 'video', 'https://www.youtube.com/watch?v=Ydc_SaQ_eRQ',
     'https://images.unsplash.com/photo-1593618998160-e34014e67546?auto=format&fit=crop&w=1200&q=80'),
    (5, 1, 'Five Useful Kitchen Hacks',
     'Printable guide covering food preparation, smart ingredient storage, and kitchen efficiency.',
     'culinary', 'guide', 'output/pdf/kitchen-hacks-guide.pdf',
     'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=80'),
    (6, 1, 'Mastering Fresh Handmade Pasta from Scratch',
     'Complete culinary video walk-through on mixing, kneading, rolling, and cutting fresh egg dough.',
     'culinary', 'video', 'https://www.youtube.com/watch?v=sBy_W023U-Q',
     'https://images.unsplash.com/photo-1556761223-4c4282c73f77?auto=format&fit=crop&w=1200&q=80'),
    (7, 1, 'Culinary Nutrition & Balanced Meal Planning',
     'Educational tutorial guide on balancing macronutrients, selecting healthy cooking fats, and smart sodium intake.',
     'educational', 'tutorial', 'output/pdf/culinary-nutrition-guide.pdf',
     'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80'),
    (8, 1, 'Kitchen Food Safety & Temperature Standards',
     'Educational reference guide detailing internal meat temperatures, the danger zone, and cross-contamination prevention.',
     'educational', 'tutorial', 'output/pdf/food-safety-temperature-tutorial.pdf',
     'https://images.unsplash.com/photo-1584483766114-2cea6facdf57?auto=format&fit=crop&w=1200&q=80'),
    (9, 1, 'Mastering Knife Skills & Prep Precision Tutorial',
     'Educational lesson on blade geometry, angle alignment, honing versus sharpening, and workstation ergonomics.',
     'educational', 'tutorial', 'output/pdf/mastering-knife-skills-tutorial.pdf',
     'https://images.unsplash.com/photo-1593618998160-e34014e67546?auto=format&fit=crop&w=1200&q=80'),
    (10, 1, 'The Science of Flavor Pairing & Aromatics',
     'Educational guide explaining sweet-sour-salty-umami balances, the Maillard reaction, and fat-soluble spice blooming.',
     'educational', 'guide', 'output/pdf/flavor-chemistry-pairing-guide.pdf',
     'https://images.unsplash.com/photo-1532336414038-cf19250c5757?auto=format&fit=crop&w=1200&q=80'),
    (11, 1, 'Step-by-Step Baking Foundations',
     'In-depth interactive educational lessons covering flour hydration, gluten development, and yeast fermentation.',
     'educational', 'tutorial', 'https://www.kingarthurbaking.com/learn',
     'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=1200&q=80');
