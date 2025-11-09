-- USERS
drop table users cascade constraints;
create table users (
   user_id    number primary key,
   name       varchar2(100) not null,
   email      varchar2(150) unique not null,
   password   varchar2(255) not null,
   phone      varchar2(20),
   role       varchar2(20) default 'user', -- 'user' or 'admin'
   status     varchar2(20) default 'active', -- 'active' or 'inactive'
   created_at date default sysdate
);

create sequence users_seq start with 1 increment by 1;

drop table viewsitem cascade constraints;
create table viewsitem (
   image_id    number primary key,
   image_url   varchar2(255),
   title       varchar2(100) not null,
   description clob
);

create sequence viewsitem_seq start with 1 increment by 1;

-- ROOMS
drop table rooms cascade constraints;

create table rooms (
   room_id         number primary key,
   room_name       varchar2(100) not null,
   description     clob,
   price_per_night number(10,2) not null,
   status          varchar2(20) default 'Available',
   image_url       varchar2(255),
   sleeps          number
);
alter table rooms drop column "VIEW";

create sequence rooms_seq start with 1 increment by 1;

-- RESTAURANT MENU
drop table restaurant_menu cascade constraints;
create table restaurant_menu (
   menu_id     number primary key,
   name        varchar2(100) not null,
   description clob,
   price       number(10,2) not null,
   category    varchar2(50),
   image_url   varchar2(255)
);

create sequence restaurant_menu_seq start with 1 increment by 1;

-- BOOKINGS
drop table bookings cascade constraints;
create table bookings (
   booking_id     number primary key,
   user_id        number
      references users ( user_id ),
   room_id        number
      references rooms ( room_id ),
   check_in_date  date not null,
   check_out_date date not null,
   status         varchar2(20) default 'Pending', -- Pending/Confirmed/Cancelled
   created_at     date default sysdate
);

create sequence bookings_seq start with 1 increment by 1;

-- RESTAURANT ORDERS
drop table restaurant_orders cascade constraints;
create table restaurant_orders (
   order_id     number primary key,
   user_id      number
      references users ( user_id ),
   order_date   date default sysdate,
   total_amount number(10,2) not null,
   status       varchar2(20) default 'Pending' -- Pending/Confirmed/Cancelled
);

create sequence restaurant_orders_seq start with 1 increment by 1;

-- ORDER ITEMS
drop table order_items cascade constraints;
create table order_items (
   order_item_id number primary key,
   order_id      number
      references restaurant_orders ( order_id ),
   menu_id       number
      references restaurant_menu ( menu_id ),
   quantity      number not null,
   price         number(10,2) not null -- price at order time
);

create sequence order_items_seq start with 1 increment by 1;

-- BOOKING PAYMENTS
drop table booking_payments cascade constraints;
create table booking_payments (
   payment_id     number primary key,
   booking_id     number
      references bookings ( booking_id ),
   user_id        number
      references users ( user_id ),
   amount         number(10,2) not null,
   method         varchar2(50) not null, -- Stripe, PayPal, Cash, etc.
   status         varchar2(20) default 'Pending', -- Paid/Pending/Failed
   payment_date   date default sysdate,
   transaction_id varchar2(255)
);

create sequence booking_payments_seq start with 1 increment by 1;

drop table guests cascade constraints;
create table guests (
   guest_id      number primary key,
   first_name    varchar2(50),
   last_name     varchar2(50),
   email         varchar2(100),
   phone         varchar2(20),
   address       varchar2(255),
   created_at    date default sysdate,
   booking_id    number
      references bookings ( booking_id ),
   room_id       number
      references rooms ( room_id ),
   payment_id    number
      references booking_payments ( payment_id ),
   order_item_id number
      references order_items ( order_item_id )
);
alter table guests drop column order_item_id;
create sequence guests_seq start with 1 increment by 1;


-- ORDER PAYMENTS
drop table order_payments cascade constraints;
create table order_payments (
   payment_id     number primary key,
   order_id       number
      references restaurant_orders ( order_id ),
   user_id        number
      references users ( user_id ),
   amount         number(10,2) not null,
   method         varchar2(50) not null, -- Stripe, PayPal, Cash, etc.
   status         varchar2(20) default 'Pending', -- Paid/Pending/Failed
   payment_date   date default sysdate,
   transaction_id varchar2(255)
);

create sequence order_payments_seq start with 1 increment by 1;

-- OPTIONAL: SYSTEM LOGS
drop table system_logs cascade constraints;
create table system_logs (
   log_id   number primary key,
   user_id  number
      references users ( user_id ),
   action   varchar2(255),
   log_date date default sysdate
);

create sequence system_logs_seq start with 1 increment by 1;

drop table contact cascade constraints;
create table contact (
   id         number primary key,
   name       varchar2(255) not null,
   email      varchar2(255) not null,
   message    clob not null,
   phone      varchar2(20),
   status     varchar2(20) default 'New', -- New/Read/Resolved
   created_at date default sysdate
);

create sequence contact_seq start with 1 increment by 1;