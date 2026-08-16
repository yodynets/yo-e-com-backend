create table categories
(
    id                 integer generated always as identity
        primary key,
    parent_id          integer
        constraint categories_parent_id_categories_id_fk
            references categories
            on delete restrict,
    is_top             boolean                     default false not null,
    menu_columns_count smallint                    default 1     not null,
    sort_order         smallint                    default 0     not null,
    is_active          boolean                     default true  not null,
    name               jsonb                                     not null,
    slug               jsonb                                     not null,
    image              jsonb,
    description        jsonb,
    meta               jsonb,
    created_at         timestamp(6) with time zone default now() not null,
    updated_at         timestamp(6) with time zone default now() not null
);

alter table categories
    owner to kplus;

create index categories_parent_id_index
    on categories (parent_id);

create index categories_slug_uk_idx
    on categories ((slug ->> 'uk'::text));

create index categories_slug_en_idx
    on categories ((slug ->> 'en'::text));

create index categories_name_uk_ci_idx
    on categories (lower(name ->> 'uk'::text));

create index categories_name_en_ci_idx
    on categories (lower(name ->> 'en'::text));

