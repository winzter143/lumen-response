DROP SCHEMA IF EXISTS consumer;
CREATE SCHEMA consumer;

--
-- LBC tracking numbers
--
CREATE SEQUENCE consumer.lbc_tracking_number_seq START WITH 71001372089163;

--
-- Table structure for table orders
--
CREATE TYPE consumer.order_status AS ENUM ('pending', 'for_pickup', 'picked_up', 'failed_pickup', 'in_transit', 'claimed', 'out_for_delivery', 'delivered', 'failed_delivery', 'return_in_transit', 'returned', 'failed_return');
CREATE TYPE consumer.payment_method AS ENUM ('credit_card', 'cod', 'otc', 'debit_card');
CREATE TYPE consumer.payment_provider AS ENUM('asiapay', 'dragonpay', 'lbc', 'lbcx');
CREATE TABLE consumer.orders
(
  id SERIAL,
  party_id INT NOT NULL,
  currency_id INT NOT NULL,
  reference_id VARCHAR(100) NOT NULL,
  pickup_address_id INT NOT NULL,
  delivery_address_id INT NOT NULL,
  return_address_id INT,
  active_segment_id INT,
  tracking_number VARCHAR(15) NOT NULL,
  payment_method consumer.payment_method NOT NULL,
  payment_provider consumer.payment_provider NOT NULL,
  status consumer.order_status NOT NULL DEFAULT 'pending',
  buyer_name VARCHAR(100) NOT NULL,
  email VARCHAR(50),
  contact_number VARCHAR(50),
  subtotal NUMERIC(14, 2) NOT NULL,
  shipping NUMERIC(14, 2),
  tax NUMERIC(14, 2),
  fee NUMERIC(14, 2),
  insurance NUMERIC(14, 2),
  grand_total NUMERIC(14, 2) NOT NULL,
  total_collected NUMERIC(14, 2) NOT NULL DEFAULT 0,
  shipping_fee NUMERIC(14,2) NOT NULL,
  insurance_fee NUMERIC(14,2) NOT NULL,
  transaction_fee NUMERIC(14,2) NOT NULL,
  metadata JSONB,
  parcel JSONB,
  ip_address VARCHAR(15),
  preferred_pickup_time VARCHAR(100),
  preferred_delivery_time VARCHAR(100),
  tat JSONB,
  pickup_attempts SMALLINT NOT NULL DEFAULT 0,
  delivery_attempts SMALLINT NOT NULL DEFAULT 0,
  status_updated_at TIMESTAMP WITH TIME ZONE,
  flagged SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by INT,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT orders_party_id_fk FOREIGN KEY (party_id) REFERENCES core.parties (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_currency_id FOREIGN KEY (currency_id) REFERENCES core.currencies (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_pickup_address_id FOREIGN KEY (pickup_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_delivery_address_id FOREIGN KEY (delivery_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_return_address_id FOREIGN KEY (return_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_tracking_number_uk UNIQUE (tracking_number),
  CONSTRAINT orders_party_id_reference_id_uk UNIQUE (party_id, reference_id)
);
CREATE INDEX orders_buyer_name_fts_idx ON consumer.orders USING gin(to_tsvector('english', buyer_name));
CREATE INDEX orders_email_fts_idx ON consumer.orders USING gin(to_tsvector('english', email));
CREATE INDEX orders_contact_number_fts_idx ON consumer.orders USING gin(to_tsvector('english', contact_number));

--
-- Table structure for table orders
--,
CREATE TYPE consumer.barcode_format_1d AS ENUM ('upc', 'ean', 'code_39', 'code_93', 'code_128', 'itf', 'codabar', 'gs1_databar', 'msi_plessey');
CREATE TYPE consumer.barcode_format_2d AS ENUM ('qr', 'datamatrix', 'pdf_417', 'aztec');
CREATE TYPE consumer.shipping_type AS ENUM ('land', 'sea', 'air');
CREATE TYPE consumer.order_segment_type AS ENUM ('pick_up', 'transfer', 'delivery');
CREATE TABLE consumer.order_segments
(
  id SERIAL,
  order_id INT NOT NULL,
  courier_party_id INT NOT NULL,
  shipping_type consumer.shipping_type NOT NULL,
  type consumer.order_segment_type NOT NULL,
  status consumer.order_status NOT NULL DEFAULT 'pending',
  currency_id INT,
  amount NUMERIC(14, 2),
  reference_id VARCHAR(100),
  pickup_address_id INT NOT NULL,
  delivery_address_id INT NOT NULL,
  barcode_format_1d consumer.barcode_format_1d NOT NULL,
  barcode_format_2d consumer.barcode_format_2d NOT NULL,
  start_date TIMESTAMP WITH TIME ZONE,
  end_date TIMESTAMP WITH TIME ZONE,
  flagged SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT order_segments_order_id_id_fk FOREIGN KEY (order_id) REFERENCES consumer.orders (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT order_segments_courier_party_id_fk FOREIGN KEY (courier_party_id) REFERENCES core.organizations (party_id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT order_segments_currency_id FOREIGN KEY (currency_id) REFERENCES core.currencies (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT order_segments_pickup_address_id FOREIGN KEY (pickup_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT order_segments_delivery_address_id FOREIGN KEY (delivery_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);

ALTER TABLE consumer.orders ADD CONSTRAINT orders_active_segment_id FOREIGN KEY (active_segment_id) REFERENCES consumer.order_segments (id) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Table structure for table order_events
--
CREATE TABLE consumer.order_events
(
  id SERIAL,
  order_segment_id INT NOT NULL,
  status consumer.order_status NOT NULL,
  remarks TEXT,
  created_by INT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT order_events_order_segment_id_fk FOREIGN KEY (order_segment_id) REFERENCES consumer.order_segments (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);

--
-- Table structure for table order_items
--
CREATE TYPE consumer.order_item_type AS ENUM ('product', 'shipping', 'tax', 'fee', 'insurance');
CREATE TABLE consumer.order_items
(
  id SERIAL,
  order_id INT NOT NULL,
  type consumer.order_item_type NOT NULL,
  description TEXT NOT NULL,
  amount NUMERIC(14, 2) NOT NULL,
  quantity INT NOT NULL,
  total NUMERIC(14, 2) NOT NULL,
  metadata JSONB,
  PRIMARY KEY (id),
  CONSTRAINT orders_items_order_id_fk FOREIGN KEY (order_id) REFERENCES consumer.orders (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);

--
-- Table structure for table deposits
--
CREATE TABLE consumer.deposits
(
  id SERIAL,
  bank VARCHAR(100) NOT NULL,
  branch VARCHAR(100) NOT NULL,
  account_name VARCHAR(100) NOT NULL,
  account_number VARCHAR(100) NOT NULL,
  reference_id VARCHAR(100),
  amount NUMERIC(14, 2) NOT NULL,
  created_by INT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);

--
-- Table structure for table charges
--
CREATE TYPE consumer.charge_status AS ENUM ('created', 'assigned', 'paid', 'remitted', 'paid_out');
CREATE TABLE consumer.charges
(
  order_id INT NOT NULL,
  status consumer.charge_status NOT NULL DEFAULT 'created',
  payment_method consumer.payment_method NOT NULL, 
  collector_party_id INT,
  deposit_id INT,
  total_amount NUMERIC(14, 2) NOT NULL,
  tendered_amount NUMERIC(14, 2) NOT NULL,
  change_amount NUMERIC(14, 2) NOT NULL,
  remarks TEXT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by INT,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (order_id),
  CONSTRAINT charges_order_id_fk FOREIGN KEY (order_id) REFERENCES consumer.orders(id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT charges_collector_party_id_fk FOREIGN KEY (collector_party_id) REFERENCES core.parties(id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT charges_deposit_id_fk FOREIGN KEY (deposit_id) REFERENCES consumer.deposits(id) ON DELETE NO ACTION ON UPDATE NO ACTION
);

--
-- Table structure for table claims
--
CREATE TYPE consumer.claim_status AS ENUM ('pending', 'verified', 'settled', 'declined');
CREATE TABLE consumer.claims
(
  order_id INT NOT NULL,
  status consumer.claim_status NOT NULL DEFAULT 'pending',
  amount NUMERIC(14, 2) NOT NULL,
  shipping_fee_flag SMALLINT NOT NULL DEFAULT 0,
  insurance_fee_flag SMALLINT NOT NULL DEFAULT 0,
  transaction_fee_flag SMALLINT NOT NULL DEFAULT 0,
  documentary_proof_url TEXT,
  reason TEXT NOT NULL,
  remarks TEXT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by INT,
  updated_at TIMESTAMP WITH TIME ZONE,
  updated_by INT,
  PRIMARY KEY (order_id),
  CONSTRAINT claims_order_id_fk FOREIGN KEY (order_id) REFERENCES consumer.orders(id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
