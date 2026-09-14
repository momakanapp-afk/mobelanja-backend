

DROP TABLE IF EXISTS `User`;
CREATE TABLE IF NOT EXISTS `User` (
  `_id` INT NOT NULL AUTO_INCREMENT,
  `clerkId` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `name` CHAR(200) NOT NULL,
  `facebook` CHAR(200) DEFAULT NULL,
  `kontak` CHAR(200) DEFAULT NULL,
  `kotakab` CHAR(200) DEFAULT NULL,
  `imageUrl`VARCHAR(1000) DEFAULT NULL,
  `stripeCustomerId`VARCHAR(255) DEFAULT NULL,
  `timestamps` TIMESTAMP DEFAULT UTC_TIMESTAMP(),
  PRIMARY KEY (`_id`),
  UNIQUE KEY usr_cid (clerkId)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `Address`;
CREATE TABLE IF NOT EXISTS `Address` (
  `_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clerkId` VARCHAR(255) NOT NULL,
  `label` VARCHAR(500) NOT NULL,
  `fullName` VARCHAR(255) NOT NULL,
  `streetAddress` VARCHAR(255) NOT NULL,
  `city` VARCHAR(255) NOT NULL,
  `state` CHAR(100) DEFAULT 'ID',
  `geo_lat` DECIMAL(11,8),
  `geo_long` DECIMAL(11,8),
  `zipCode`  CHAR(50) NOT NULL,
  `phoneNumber`  CHAR(100) NOT NULL,
  `isDefault` BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (`_id`),
  KEY adrs_cid (clerkId)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `Wishlist`;
CREATE TABLE IF NOT EXISTS `Wishlist` (
  `_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clerkId` VARCHAR(255) NOT NULL,
  `Product_id` BIGINT UNSIGNED NOT NULL,
  `timestamps` TIMESTAMP DEFAULT UTC_TIMESTAMP(),
  PRIMARY KEY (`_id`),
  KEY wsh_cid (clerkId),
  KEY wsh_pid (Product_id)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `Product`;
CREATE TABLE IF NOT EXISTS `Product` (
  `_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Toko_id` INT UNSIGNED DEFAULT NULL,
  `barcode` VARCHAR(255) DEFAULT NULL,
  `name` VARCHAR(1000) DEFAULT NULL,
  `description` VARCHAR(1000) DEFAULT NULL,
  `price` DECIMAL(10,2) DEFAULT NULL,
  `stock` INT DEFAULT NULL,
  `category` CHAR(250) DEFAULT NULL,
  `images` VARCHAR(1000) DEFAULT NULL,
  `averageRating` DECIMAL(3,2) DEFAULT 0,
  `totalReviews` DECIMAL(10,0) DEFAULT 0,
  `timestamps` TIMESTAMP DEFAULT UTC_TIMESTAMP(),
  `aktif` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`_id`),
  KEY `pid_tid` (`Toko_id`) ,
  KEY `pid_brc` (`barcode`) ,
  KEY `pid_akt` (`aktif`) ,
  FULLTEXT `pid_f_ndc` (`name`,`description`,`category`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `Toko`;
CREATE TABLE IF NOT EXISTS `Toko` (
  `_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clerkId` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `kotakab` CHAR(80) NOT NULL,
  `alamat` VARCHAR(255) NOT NULL,
  `kodepos` CHAR(20) NOT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `imageurl` VARCHAR(255) DEFAULT NULL,
  `facebook` CHAR(100) DEFAULT NULL,
  `instagram` CHAR(100) DEFAULT NULL,
  `kontak` CHAR(100) NOT NULL,
  `geo_lat` DECIMAL(11,8) DEFAULT 0,
  `geo_long` DECIMAL(11,8) DEFAULT 0,  
  `desc` TEXT NOT NULL,
  `pintoko` CHAR(10) DEFAULT NULL,
  `pinjoin` VARCHAR(255) DEFAULT NULL,
  `timestamps` TIMESTAMP DEFAULT UTC_TIMESTAMP(),
  PRIMARY KEY (`_id`),
  KEY tko_cid (clerkId),
  KEY tko_pnt (pintoko),
  FULLTEXT `tko_f_ndc` (`name`, `desc`,`website`,`facebook`,`instagram`)
) ENGINE=InnoDB;

-- Afiliasi / Reseller : Multi admin pengelola toko
DROP TABLE IF EXISTS `Toko_grup`;
CREATE TABLE IF NOT EXISTS `Toko_grup` (
  `_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Toko_id` BIGINT UNSIGNED NOT NULL,
  `clerkId` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY tkg_cid (clerkId),
  KEY tkg_tid (Toko_id)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `Cart`;
CREATE TABLE IF NOT EXISTS `Cart` (
  `_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clerkId` VARCHAR(255) NOT NULL,
  `Toko_id` INT UNSIGNED DEFAULT NULL,
  `timestamps` TIMESTAMP DEFAULT UTC_TIMESTAMP(),
  PRIMARY KEY (`_id`),
  KEY crt_tid (`Toko_id`),
  KEY crt_cid (clerkId)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `CartItem`;
CREATE TABLE IF NOT EXISTS `CartItem` (
  `_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Cart_id` BIGINT UNSIGNED DEFAULT NULL,
  `Product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT DEFAULT 1,
  `timestamps` TIMESTAMP DEFAULT UTC_TIMESTAMP(),
  PRIMARY KEY (`_id`),
  KEY `citm_cri` (Cart_id),
  KEY `citm_pdi` (Product_id)
) ENGINE=InnoDB;

