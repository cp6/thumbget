create table videos
(
    id            int auto_increment
        primary key,
    video_id      char(11)     not null,
    channel_id    varchar(64)  not null,
    uploaded_at   datetime     not null,
    title         varchar(255) null,
    channel_title varchar(255) not null,
    inserted_at   datetime     null,
    updated_at    datetime     null,
    constraint videos_uk2
        unique (video_id)
);
