-- Demo brand, categories, products, and a sample CMS pages
INSERT INTO `brands` (`id`,`name`,`slug`,`is_active`) VALUES
(1,'LeatherHood','leatherhood',1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO `categories` (`id`,`parent_id`,`name`,`slug`,`description`,`is_active`,`is_featured`,`sort_order`) VALUES
(1,NULL,'Belts','belts','Genuine leather belts.',1,1,1),
(2,NULL,'Wallets','wallets','Hand-stitched wallets.',1,1,2),
(3,NULL,'Shoes','shoes','Leather shoes & loafers.',1,1,3),
(4,NULL,'Bags','bags','Office and travel leather bags.',1,1,4)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO `products`
 (`sku`,`name`,`slug`,`brand_id`,`primary_category_id`,`template_type`,`short_description`,`description`,
  `price`,`compare_at_price`,`stock_qty`,`status`,`is_featured`,`is_popular`,`is_new`,`published_at`)
VALUES
('LH-BLT-001','Classic Black Leather Belt','classic-black-leather-belt',1,1,'default',
 'Hand-stitched full-grain leather belt with brass buckle.',
 '<p>Crafted from premium full-grain cowhide leather. Solid brass buckle. Hand-stitched edges.</p>',
 1490,1990,50,'active',1,1,1,NOW()),
('LH-WLT-001','Bifold Brown Wallet','bifold-brown-wallet',1,2,'minimal',
 'Slim bifold wallet with 6 card slots and RFID protection.',
 '<p>Slim profile, deep card pockets, RFID blocking lining.</p>',
 1290,1690,40,'active',1,1,0,NOW()),
('LH-SHO-001','Oxford Leather Shoe','oxford-leather-shoe',1,3,'luxury',
 'Classic Oxford with leather sole and Goodyear-welted construction.',
 '<p>Goodyear welted, hand-finished, polished calfskin upper.</p>',
 4990,5990,15,'active',1,0,1,NOW()),
('LH-BAG-001','Executive Briefcase','executive-briefcase',1,4,'storyteller',
 'Full-grain executive briefcase with padded laptop sleeve.',
 '<p>Fits 15" laptops, padded compartments, brass hardware.</p>',
 7990,9990,8,'active',1,1,1,NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO `pages` (`title`,`slug`,`content`,`status`,`published_at`) VALUES
('Privacy Policy','privacy-policy',
 '<h2>Privacy Policy</h2><p>We respect your privacy. This page explains what data we collect at LeatherHood, how we use it, and your rights.</p>',
 'published',NOW()),
('Terms & Conditions','terms-and-conditions',
 '<h2>Terms & Conditions</h2><p>By using leatherhoodbd.com you agree to these terms...</p>',
 'published',NOW()),
('Refund & Returns','refund-and-returns',
 '<h2>Refund & Returns</h2><p>7-day easy returns on unused, original-condition products. Contact us within 7 days of delivery.</p>',
 'published',NOW()),
('Contact Us','contact',
 '<h2>Contact Us</h2><p>Reach our support team Sat–Thu, 10am–6pm Asia/Dhaka. Phone, email, and the form on this page all work.</p>',
 'published',NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title);
