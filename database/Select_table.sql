-- 1. Simple SELECT with multiple columns
select user_id,
       name,
       email,
       status
  from users
 where status = 'active';

-- 2. SELECT with ORDER BY
select room_id,
       room_name,
       price_per_night
  from rooms
 order by price_per_night desc;

-- 3. SELECT with GROUP BY and aggregate
select status,
       count(*) as booking_count
  from bookings
 group by status
 order by booking_count desc;

-- 4. LEFT JOIN example (all users, even if no bookings)
select u.user_id,
       u.name,
       b.booking_id,
       b.status
  from users u
  left join bookings b
on u.user_id = b.user_id
 order by u.user_id;

-- 5. RIGHT JOIN example (all bookings, even if user is missing)
select u.user_id,
       u.name,
       b.booking_id,
       b.status
  from users u
 right join bookings b
on u.user_id = b.user_id
 order by b.booking_id;

-- 6. SELECT with multiple JOINs and GROUP BY
select r.room_name,
       count(b.booking_id) as total_bookings,
       avg(bk.amount) as avg_payment
  from rooms r
  left join bookings b
on r.room_id = b.room_id
  left join booking_payments bk
on b.booking_id = bk.booking_id
 group by r.room_name
 order by total_bookings desc;

-- 7. SELECT with WHERE, GROUP BY, HAVING
select method,
       count(*) as payment_count,
       sum(amount) as total_amount
  from booking_payments
 where status = 'Paid'
 group by method
having sum(amount) > 100
 order by total_amount desc;

-- 8. SELECT with INNER JOIN and multiple columns
select o.order_id,
       u.name as user_name,
       o.total_amount,
       o.status
  from restaurant_orders o
 inner join users u
on o.user_id = u.user_id
 order by o.order_id desc;

-- 9. SELECT with subquery
select room_id,
       room_name
  from rooms
 where room_id in (
   select room_id
     from bookings
    where status = 'Confirmed'
);

-- 10. SELECT with aliases and functions
select u.name as customer,
       count(b.booking_id) as num_bookings
  from users u
  left join bookings b
on u.user_id = b.user_id
 group by u.name
 order by num_bookings desc;

-- End of examples