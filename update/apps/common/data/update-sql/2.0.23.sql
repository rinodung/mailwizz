--
-- Update sql for MailWizz EMA from version 2.0.22 to 2.0.23
--

--
-- Add support for emojis in campaign table  
--
ALTER TABLE `campaign` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `campaign` CHANGE `from_name` `from_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;