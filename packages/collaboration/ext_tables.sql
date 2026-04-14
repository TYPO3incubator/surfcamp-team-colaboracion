# Define table and fields for sys_event_messages
CREATE TABLE sys_event_messages (
    timestamp int(10) unsigned DEFAULT '0' NOT NULL,
    owner_id int(10) unsigned DEFAULT '0' NOT NULL,
    owner_name varchar(255) NOT NULL,
    users_to_inform varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    message json NOT NULL,
);

CREATE TABLE sys_collaboration_event (
    uid INT AUTO_INCREMENT PRIMARY KEY,
    timestamp int(10) unsigned DEFAULT '0' NOT NULL,
    user_id INT NOT NULL,
    page_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL
);