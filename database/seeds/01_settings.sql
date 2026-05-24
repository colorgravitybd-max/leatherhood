-- Default settings + default super admin (password: ChangeMe!2026)
-- Hash generated with PHP password_hash('ChangeMe!2026', PASSWORD_BCRYPT)
INSERT INTO `users` (`name`,`email`,`phone`,`password`,`role`,`is_active`)
VALUES ('Owner','admin@leatherhoodbd.com','01700000000',
        '$2y$12$P9iDUlG.qukTX2mRaWDfb.51xDkcbKaU4fh3syU.skVAIv8g552Lu','super_admin',1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `settings` (`key_name`,`value`,`group_name`,`type`) VALUES
('site_name','LeatherHood','general','text'),
('site_tagline','Premium Leather. Crafted in Bangladesh.','general','text'),
('contact_phone','+8801700000000','general','text'),
('contact_email','support@leatherhoodbd.com','general','text'),
('contact_address','Dhaka, Bangladesh','general','text'),
('hero_headline','Crafted in Bangladesh. Built for a Lifetime.','homepage','text'),
('hero_subline','Genuine full-grain leather. Hand-stitched. One-year warranty.','homepage','text'),
('cod_enabled','1','payments','boolean'),
('bkash_enabled','0','payments','boolean'),
('shipping_inside_dhaka','70','shipping','number'),
('shipping_outside_dhaka','130','shipping','number'),
('free_shipping_threshold','5000','shipping','number'),
('otp_template','Your LeatherHood OTP is {OTP}. Valid for 10 minutes.','sms','text'),
('order_sms_template','LeatherHood: Order #{ORDER_NO} confirmed. Track: {TRACK}','sms','text'),
('shipped_sms_template','LeatherHood: Order #{ORDER_NO} shipped. Track: {TRACK}','sms','text'),
('warranty_period','1 Year Manufacturer Warranty','footer','text'),
('return_policy','7-Day Easy Returns','footer','text')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

INSERT INTO `home_sections` (`type`,`title`,`subtitle`,`payload`,`sort_order`,`is_active`) VALUES
('hero','Crafted in Bangladesh. Built for a Lifetime.',
  'Genuine full-grain leather. Hand-stitched. One-year warranty.',
  JSON_OBJECT('cta_text','Shop the Collection','cta_url','/shop','image','/assets/images/hero.webp'), 1, 1),
('popular_grid','Most Popular','Bestsellers loved by 10,000+ customers',
  JSON_OBJECT('limit',8,'badge','Bestseller'), 2, 1),
('category_strip','Shop by Category','Belts, Wallets, Shoes & more',
  JSON_OBJECT('categories', JSON_ARRAY('belts','wallets','shoes','bags')), 3, 1),
('banner_split','The Belt Collection','Everyday essential, made to last decades',
  JSON_OBJECT('left_image','/assets/images/cat-belts.webp','right_image','/assets/images/cat-wallets.webp',
              'left_url','/shop?cat=belts','right_url','/shop?cat=wallets'), 4, 1),
('trust_badges','Why LeatherHood','Promises we keep at every order',
  JSON_OBJECT('badges', JSON_ARRAY(
     JSON_OBJECT('icon','shield','title','1 Year Warranty','desc','On every product'),
     JSON_OBJECT('icon','truck','title','Fast Delivery','desc','Inside Dhaka in 24h'),
     JSON_OBJECT('icon','refresh','title','Easy Returns','desc','7-day no-questions returns'),
     JSON_OBJECT('icon','badge','title','100% Genuine','desc','Full-grain leather only')
  )), 5, 1)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);
