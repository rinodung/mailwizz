--
-- Update sql for MailWizz EMA from version 2.0.24 to 2.0.25
--

--
-- Alter the `campaign_option` table
--
ALTER TABLE `campaign_option` ADD `forward_friend_subject` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `preheader`;