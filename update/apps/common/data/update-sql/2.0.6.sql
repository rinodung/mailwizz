--
-- Update sql for MailWizz EMA from version 2.0.5 to 2.0.6
--

ALTER TABLE `list_field` 
    CHANGE `visibility` `visibility` ENUM('visible','hidden','none') 
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible';

UPDATE `list_field` SET `visibility` = 'none' WHERE `visibility` = 'hidden';

ALTER TABLE `survey_field`
    CHANGE `visibility` `visibility` ENUM('visible','hidden','none')
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible';

UPDATE `survey_field` SET `visibility` = 'none' WHERE `visibility` = 'hidden';