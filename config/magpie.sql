-- Not final schema!

drop table if exists magpie_customer_data;

create table magpie_customer_data(
    id int(6) not null auto_increment,
    email text,
    first_name text,
    last_name text,
    phone text,
    user_role text,
    user_level tinyint,
    product_vendor_owner text,
    product_vendor text,
    billing_first_name text,
    billing_last_name text,
    billing_company text,
    billing_address_1 text,
    billing_address_2 text,
    billing_city text,
    billing_postcode text,
    billing_country text,
    billing_state text,
    billing_phone text,
    billing_email text,
    shipping_first_name text,
    shipping_last_name text,
    shipping_company text,
    shipping_address_1 text,
    shipping_address_2 text,
    shipping_city text,
    shipping_postcode text,
    shipping_country text,
    shipping_state text,
    created_at datetime default current_timestamp,
    updated_at datetime on update current_timestamp,
    primary key (id)
);

drop table if exists magpie_order_status;

create table magpie_order_status(
    id int(6) not null auto_increment,
    order_id int(6), 
    order_status text,
    message text,
    created_at datetime default current_timestamp,
    updated_at datetime on update current_timestamp,
    primary key (id)
);

drop table if exists magpie_customer;

create table magpie_customer(
    id int(6) not null auto_increment,
    customer_id text,
    account_balance double,
    created datetime,
    currency tinytext,
    default_source text,
    delinquent bool,
    description text,
    email text,
    sources mediumtext,
    source_type text,
    object text,
    created_at datetime default current_timestamp,
    updated_at datetime on update current_timestamp,
    primary key (id)
);

drop table if exists magpie_charge;

create table magpie_charge(
    id int(6) not null auto_increment,
    order_id int(6),
    charge_id text,
    charge_details text,
    created_at datetime default current_timestamp,
    updated_at datetime on update current_timestamp,
    primary key (id)
);

drop table if exists magpie_token;

create table magpie_token(
    id int(6) not null auto_increment,
    order_id int(6),
    token_id text,
    object text,
    created_at datetime default current_timestamp,
    updated_at datetime on update current_timestamp,
    primary key (id)
);
