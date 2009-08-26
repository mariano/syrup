CREATE TABLE `robot_task_actions`(
    `id` CHAR(36) NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `weight` INT NOT NULL default 0,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY(`id`)
);

CREATE TABLE `robot_tasks`(
    `id` CHAR(36) NOT NULL,
    `robot_task_action_id` CHAR(36) NOT NULL,
    `status` VARCHAR(255) NOT NULL default 'pending',
    `parameters` BLOB default NULL,
    `scheduled` DATETIME NOT NULL,
    `started` DATETIME default NULL,
    `finished` DATETIME default NULL,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY(`id`)
);

ALTER TABLE `robot_task_actions`
    ADD UNIQUE KEY `action`(`action`);

ALTER TABLE `robot_tasks`
    ADD KEY `robot_task_action_id`(`robot_task_action_id`),
    ADD CONSTRAINT `robot_tasks__robot_task_actions` FOREIGN KEY(`robot_task_action_id`) REFERENCES `robot_task_actions`(`id`);
