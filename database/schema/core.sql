DROP SCHEMA IF EXISTS core;
CREATE SCHEMA core;

--
-- Table structure for table countries
--
CREATE TABLE core.countries
(
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  code CHAR(2) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT countries_code_uk UNIQUE (code),
  CONSTRAINT countries_name_uk UNIQUE (name)
);
CREATE INDEX countries_code_fts_idx ON core.countries USING gin(to_tsvector('english', code));
CREATE INDEX countries_name_fts_idx ON core.countries USING gin(to_tsvector('english', name));

--
-- Table structure for table states
--
CREATE TABLE core.states
(
  id SERIAL,
  country_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(25),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT states_country_id_fk FOREIGN KEY (country_id) REFERENCES core.countries (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT states_country_id_name_uk UNIQUE (country_id, name)
);
CREATE INDEX states_name_idx ON core.states (name);

--
-- Table structure for table cities
--
CREATE TABLE core.cities
(
  id SERIAL,
  country_id INT NOT NULL,
  state_id INT,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(25),
  timezone VARCHAR(50),
  latitude NUMERIC(7, 4),
  longitude NUMERIC(7, 4),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT cities_country_id_fk FOREIGN KEY (country_id) REFERENCES core.countries (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT cities_state_id_fk FOREIGN KEY (state_id) REFERENCES core.states (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT cities_country_id_state_id_name_uk UNIQUE (country_id, state_id, name)
);
CREATE INDEX cities_name_idx ON core.cities (name);

--
-- Table structure for table communities
--
CREATE TABLE core.communities
(
  id SERIAL,
  city_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(25),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT communities_city_id_fk FOREIGN KEY (city_id) REFERENCES core.cities (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT communities_city_id_name_uk UNIQUE (city_id, name)
);
CREATE INDEX communities_name_idx ON core.communities (name);

--
-- Table structure for table locations
--
CREATE TABLE core.locations
(
  id SERIAL,
  country_id INT NOT NULL,
  state_id INT,
  city_id INT,
  community_id INT,
  name VARCHAR(255) NOT NULL,
  postal_code VARCHAR(50),
  latitude NUMERIC(7, 4),
  longitude NUMERIC(7, 4),
  created_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT locations_country_id_fk FOREIGN KEY (country_id) REFERENCES core.countries (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT locations_state_id_fk FOREIGN KEY (state_id) REFERENCES core.states (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT locations_city_id_fk FOREIGN KEY (city_id) REFERENCES core.cities (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT locations_community_id_fk FOREIGN KEY (community_id) REFERENCES core.communities (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT locations_country_state_city_community_name_postal_code_uk UNIQUE (country_id, state_id, city_id, community_id, name, postal_code)
);
CREATE INDEX locations_name_idx ON core.locations (name);
CREATE INDEX locations_name_fts_idx ON core.locations USING gin(to_tsvector('english', name));
CREATE INDEX locations_postal_code_idx ON core.locations (postal_code);

--
-- Table structure for table currencies
--
CREATE TABLE core.currencies
(
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  code CHAR(3) NOT NULL,
  symbol VARCHAR(5),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT currencies_code_uk UNIQUE (code),
  CONSTRAINT currencies_name_uk UNIQUE (name)
);

--
-- Table structure for table parties
--
CREATE TYPE core.party_type AS ENUM ('user', 'organization');
CREATE TABLE core.parties
(
  id SERIAL,
  type core.party_type NOT NULL,
  status SMALLINT NOT NULL DEFAULT 1,
  metadata JSONB,
  created_by INT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by INT,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id)
);
CREATE INDEX parties_type_idx ON core.parties (type);

--
-- Table structure for table users
--
CREATE TYPE core.gender AS ENUM ('male', 'female');
CREATE TABLE core.users
(
  party_id SERIAL,
  login_id VARCHAR(50) NOT NULL,
  email VARCHAR(50),
  phone_number VARCHAR(50),
  password VARCHAR(60),
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  middle_name VARCHAR(50),
  nickname VARCHAR(50),
  birthdate DATE,
  gender core.gender,
  validated SMALLINT NOT NULL DEFAULT 0,
  last_visit_date TIMESTAMP WITH TIME ZONE,
  last_login_ip VARCHAR(15),
  PRIMARY KEY (party_id),
  CONSTRAINT users_party_id_fk FOREIGN KEY (party_id) REFERENCES core.parties (id) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT users_login_id_uk UNIQUE (login_id),
  CONSTRAINT users_email_uk UNIQUE (email),
  CONSTRAINT users_phone_number_uk UNIQUE (phone_number)
);

--
-- Table structure for table organizations
--
CREATE TYPE core.organization_type AS ENUM ('company', 'merchant', 'courier');
CREATE TABLE core.organizations
(
  party_id SERIAL,
  type core.organization_type NOT NULL,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (party_id),
  CONSTRAINT organizations_name_name_uk UNIQUE (name),
  CONSTRAINT organizations_party_id_fk FOREIGN KEY (party_id) REFERENCES core.parties (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
CREATE INDEX organizations_type_idx ON core.organizations (type);
CREATE INDEX organizations_name_fts_idx ON core.organizations USING gin(to_tsvector('english', name));

--
-- Table structure for table relationships
--
CREATE TYPE core.relationship_type AS ENUM ('employee_of', 'friend_of');
CREATE TABLE core.relationships (
  id SERIAL,
  from_party_id INT NOT NULL,
  type core.relationship_type NOT NULL,
  to_party_id INT NOT NULL,
  created_by INT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT relationships_from_to_party_id_rel_type_uk UNIQUE (from_party_id, to_party_id, type),
  CONSTRAINT relationships_from_party_id_fk FOREIGN KEY (from_party_id) REFERENCES core.parties (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT relationships_to_party_id_fk FOREIGN KEY (to_party_id) REFERENCES core.parties (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
CREATE INDEX relationships_from_party_id_idx ON core.relationships (from_party_id);
CREATE INDEX relationships_to_party_id_idx ON core.relationships (to_party_id);
CREATE INDEX relationships_type_idx ON core.relationships (type);

--
-- Table structure for roles
--
CREATE TABLE core.roles (
  id SERIAL,
  parent_id INT,
  name VARCHAR(50) NOT NULL,
  display_name VARCHAR(50) NOT NULL,
  permissions JSONB,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT roles_name_uk UNIQUE (name)
);

--
-- Table structure for party_roles
--
CREATE TABLE core.party_roles (
  id SERIAL,
  party_id INT NOT NULL,
  role_id INT NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT party_roles_party_id_fk FOREIGN KEY (party_id) REFERENCES core.parties (id),
  CONSTRAINT party_roles_role_id_fk FOREIGN KEY (role_id) REFERENCES core.roles (id),
  CONSTRAINT party_roles_party_id_role_id_uk UNIQUE (party_id, role_id)
);

--
-- Table structure for table addresses
--
CREATE TYPE core.address_type AS ENUM ('pickup', 'delivery', 'warehouse');
CREATE TABLE core.addresses
(
  id SERIAL,
  party_id INT NOT NULL,
  type core.address_type NOT NULL,
  name VARCHAR(255) NOT NULL,
  title VARCHAR(50),
  email VARCHAR(50),
  phone_number VARCHAR(50),
  mobile_number VARCHAR(50),
  fax_number VARCHAR(50),
  company VARCHAR(255),
  line_1 TEXT NOT NULL,
  line_2 TEXT,
  city TEXT NOT NULL,
  state TEXT NOT NULL,
  postal_code VARCHAR(50) NOT NULL,
  country_id INT NOT NULL,
  remarks TEXT,
  hash VARCHAR(32) NOT NULL,
  created_by INT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by INT,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT addresses_party_id_fk FOREIGN KEY (party_id) REFERENCES core.parties (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT addresses_country_id_fk FOREIGN KEY (country_id) REFERENCES core.countries (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT addresses_hash_uk UNIQUE (hash)
);
CREATE INDEX addresses_type_idx ON core.addresses (type);
CREATE INDEX addresses_name_fts_idx ON core.addresses USING gin(to_tsvector('english', name));
CREATE INDEX addresses_email_fts_idx ON core.addresses USING gin(to_tsvector('english', email));
CREATE INDEX addresses_phone_number_fts_idx ON core.addresses USING gin(to_tsvector('english', phone_number));
CREATE INDEX addresses_mobile_number_fts_idx ON core.addresses USING gin(to_tsvector('english', mobile_number));
CREATE INDEX addresses_company_fts_idx ON core.addresses USING gin(to_tsvector('english', company));
CREATE INDEX addresses_line_1_fts_idx ON core.addresses USING gin(to_tsvector('english', line_1));
CREATE INDEX addresses_line_2_fts_idx ON core.addresses USING gin(to_tsvector('english', line_2));
CREATE INDEX addresses_city_fts_idx ON core.addresses USING gin(to_tsvector('english', city));
CREATE INDEX addresses_state_fts_idx ON core.addresses USING gin(to_tsvector('english', state));
CREATE INDEX addresses_postal_code_fts_idx ON core.addresses USING gin(to_tsvector('english', postal_code));

--
-- Table structure for table api_keys
--
CREATE TABLE core.api_keys
(
  id SERIAL,
  party_id INT NOT NULL,
  api_key TEXT NOT NULL,
  secret_key TEXT NOT NULL,
  status SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL,
  expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT api_keys_api_key_uk UNIQUE (api_key),
  CONSTRAINT api_keys_party_id_fk FOREIGN KEY (party_id) REFERENCES core.parties (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);

--
-- Table structure for table cron_scripts
--
CREATE TABLE core.cron_scripts
(
  id SERIAL,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  metadata JSONB,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL,
  last_run TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT cron_scripts_name_uk UNIQUE (name)
);
