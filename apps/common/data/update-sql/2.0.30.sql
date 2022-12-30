--
-- Update sql for MailWizz EMA from version 2.0.29 to 2.0.30
--

--
-- Table structure for table `menu_zone`
--

DROP TABLE IF EXISTS `menu_zone`;
CREATE TABLE IF NOT EXISTS `menu_zone` (
    `zone_id` int(11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `name` varchar(100) NOT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`zone_id`),
    UNIQUE KEY `menu_zone_UNIQUE` (`slug`(191))
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
CREATE TABLE IF NOT EXISTS `menu` (
    `menu_id` int(11) NOT NULL AUTO_INCREMENT,
    `zone_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `status` char(15) NOT NULL DEFAULT 'active',
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
    ADD CONSTRAINT `fk_menu_menu_zone` FOREIGN KEY (`zone_id`) REFERENCES `menu_zone` (`zone_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Table structure for table `menu_item`
--

DROP TABLE IF EXISTS `menu_item`;
CREATE TABLE IF NOT EXISTS `menu_item` (
    `item_id` int(11) NOT NULL AUTO_INCREMENT,
    `menu_id` int(11) NOT NULL,
    `label` varchar(100) NOT NULL,
    `title` varchar(100) NOT NULL,
    `url` varchar(255) NOT NULL,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `menu_item`
--
ALTER TABLE `menu_item`
    ADD CONSTRAINT `fk_menu_item_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- -----------------------------------------------------
-- Dumping data for table `menu_zone`
-- -----------------------------------------------------

INSERT INTO `menu_zone` (`zone_id`, `slug`, `name`, `date_added`, `last_updated`) VALUES
    (1, 'frontend-header', 'Frontend - Header', NOW(), NOW()),
    (2, 'frontend-footer', 'Frontend - Footer', NOW(), NOW());

-- --------------------------------------------------------

--
-- Alter `tracking_domain` table
--
ALTER TABLE `tracking_domain` ADD `verified` ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER `scheme`;
UPDATE `tracking_domain` SET `verified` = 'yes' WHERE `verified` = 'no';
-- --------------------------------------------------------

-- -----------------------------------------------------
-- Increase category column size to 100 from 32
-- -----------------------------------------------------
ALTER TABLE `translation_source_message` CHANGE `category` `category` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

