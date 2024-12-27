-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: mysql
-- Время создания: Дек 27 2024 г., 14:53
-- Версия сервера: 8.4.3
-- Версия PHP: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- База данных: `mydb`
--

-- --------------------------------------------------------

--
-- Структура таблицы `mailings_list`
--

CREATE TABLE `mailings_list` (
                                 `id` int NOT NULL,
                                 `added` datetime NOT NULL,
                                 `title` varchar(255) NOT NULL,
                                 `text` text NOT NULL,
                                 `state` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
                            `id` int NOT NULL,
                            `mailing_id` int NOT NULL,
                            `subscriber_number` varchar(13) NOT NULL,
                            `sent_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `subscribers`
--

CREATE TABLE `subscribers` (
                               `number` varchar(13) NOT NULL,
                               `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `mailings_list`
--
ALTER TABLE `mailings_list`
    ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
    ADD PRIMARY KEY (`id`),
  ADD KEY `mailing_id_and_subscriber_number` (`mailing_id`,`subscriber_number`);

--
-- Индексы таблицы `subscribers`
--
ALTER TABLE `subscribers`
    ADD UNIQUE KEY `number` (`number`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `mailings_list`
--
ALTER TABLE `mailings_list`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;
