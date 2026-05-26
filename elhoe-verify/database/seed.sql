-- ELHOE Verify - seed.sql
-- Default super admin (email: admin@elhoe.com / password: ChangeMe!2026)
-- Hash regenerated via: password_hash('ChangeMe!2026', PASSWORD_BCRYPT)
INSERT INTO `admin_users` (`name`, `email`, `password_hash`, `role`)
VALUES (
  'ELHOE Admin',
  'admin@elhoe.com',
  '$2y$12$8eFAlsyttuLEvwabqv5d1eZ.cnnb3UOMPRf/GrfGgC2PryFKw8DVG',
  'superadmin'
);

-- Sample product (delete in production)
INSERT INTO `products`
  (`title`, `title_bn`, `description`, `description_bn`, `ingredients`,
   `how_to_use`, `how_to_use_bn`, `image_url`, `wordpress_url`)
VALUES (
  'ELHOE Hydra-Glow Serum 30ml',
  'এলহো হাইড্রা-গ্লো সিরাম ৩০মি.লি.',
  'A featherweight luxury serum infused with hyaluronic acid and niacinamide for radiant, plump skin.',
  'হায়ালুরোনিক অ্যাসিড ও নিয়াসিনামাইড সমৃদ্ধ আল্ট্রা-লাইট সিরাম, যা ত্বককে উজ্জ্বল ও প্লাম্প করে তোলে।',
  'Aqua, Hyaluronic Acid, Niacinamide 5%, Panthenol, Vitamin E, Allantoin.',
  '1) Cleanse and tone. 2) Dispense 3-4 drops onto the palm. 3) Press gently into face and neck. 4) Follow with moisturizer and SPF.',
  '১) মুখ পরিষ্কার করে টোনার লাগান। ২) হাতে ৩-৪ ফোঁটা নিন। ৩) মুখ ও গলায় হালকা চাপ দিয়ে লাগান। ৪) ময়েশ্চারাইজার ও সানস্ক্রিন ব্যবহার করুন।',
  '/assets/uploads/sample-serum.jpg',
  'https://elhoe.com/product/hydra-glow-serum'
);

-- Two demo codes for the sample product
INSERT INTO `verification_codes` (`product_id`, `code`, `code_type`, `batch_number`, `expiry_date`)
VALUES
  (1, 'ELHOE-DEMO-UNIVERSAL', 'universal', 'BATCH-DEMO', DATE_ADD(CURDATE(), INTERVAL 24 MONTH)),
  (1, 'ELHOE-DEMO-UNIQUE-001', 'unique',    'BATCH-DEMO', DATE_ADD(CURDATE(), INTERVAL 24 MONTH));
