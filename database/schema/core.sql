DROP SCHEMA IF EXISTS core;
CREATE SCHEMA core;

--
-- Table structure for table `locations`
--
CREATE TYPE core.location_type AS ENUM ('country', 'state', 'region', 'city', 'district');
CREATE TABLE core.locations (
  id SERIAL,
  code VARCHAR(50),
  name VARCHAR(255) NOT NULL,
  type core.location_type NOT NULL,
  parent_id INT,
  postal_code VARCHAR(50),
  status SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id)
);
CREATE INDEX locations_code_fts_idx ON core.locations USING gin(to_tsvector('english', code));
CREATE INDEX locations_name_fts_idx ON core.locations USING gin(to_tsvector('english', name));
CREATE INDEX locations_type_idx ON core.locations (type);

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
  CONSTRAINT addresses_country_id_fk FOREIGN KEY (country_id) REFERENCES core.locations (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
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
  name VARCHAR(50) NOT NULL,
  party_id INT NOT NULL,
  api_key TEXT NOT NULL,
  secret_key TEXT NOT NULL,
  token TEXT,
  token_expires_at TIMESTAMP WITH TIME ZONE,
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
