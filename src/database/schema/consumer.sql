DROP SCHEMA IF EXISTS consumer;
CREATE SCHEMA consumer;

--
-- LBC tracking numbers
--
CREATE SEQUENCE consumer.lbc_tracking_number_seq START WITH 71001372089163;

--
-- Table structure for table orders
--
CREATE TYPE consumer.order_status AS ENUM ('pending', 'for_pickup', 'picked_up', 'failed_pickup', 'in_transit', 'claimed', 'delivered', 'return_in_transit', 'returned', 'failed_return');
CREATE TYPE consumer.payment_method AS ENUM ('credit_card', 'bank_deposit', 'mobile', 'cod');
CREATE TABLE consumer.orders
(
  id SERIAL,
  org_party_id INT NOT NULL,
  currency_id INT NOT NULL,
  reference_id VARCHAR(100) NOT NULL,
  pickup_address_id INT NOT NULL,
  delivery_address_id INT NOT NULL,
  return_address_id INT,
  active_segment_id INT,
  tracking_number VARCHAR(15) NOT NULL,
  payment_method consumer.payment_method,
  status consumer.order_status NOT NULL DEFAULT 'pending',
  buyer_name VARCHAR(100) NOT NULL,
  email VARCHAR(50),
  contact_number VARCHAR(50),
  subtotal NUMERIC(14, 2) NOT NULL,
  shipping NUMERIC(14, 2) NOT NULL,
  tax NUMERIC(14, 2) NOT NULL,
  fee NUMERIC(14, 2) NOT NULL,
  insurance NUMERIC(14, 2) NOT NULL,
  grand_total NUMERIC(14, 2) NOT NULL,
  total_collected NUMERIC(14, 2) NOT NULL DEFAULT 0,
  shipping_fee NUMERIC(14,2) NOT NULL,
  insurance_fee NUMERIC(14,2) NOT NULL,
  service_fee NUMERIC(14,2) NOT NULL,
  metadata JSONB,
  parcel JSONB,
  ip_address VARCHAR(15),
  preferred_pickup_time VARCHAR(100),
  preferred_delivery_time VARCHAR(100),
  flagged SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by INT,
  updated_at TIMESTAMP WITH TIME ZONE,
  pickup_date TIMESTAMP WITH TIME ZONE,
  pickup_attempts SMALLINT NOT NULL DEFAULT 0,
  last_status_update TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT orders_org_party_id_fk FOREIGN KEY (org_party_id) REFERENCES core.organizations (party_id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_currency_id FOREIGN KEY (currency_id) REFERENCES core.currencies (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_pickup_address_id FOREIGN KEY (pickup_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_delivery_address_id FOREIGN KEY (delivery_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_return_address_id FOREIGN KEY (return_address_id) REFERENCES core.addresses (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_active_segment_id FOREIGN KEY (active_segment_id) REFERENCES consumer.order_segments (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT orders_tracking_number_uk UNIQUE (tracking_number),
  CONSTRAINT orders_org_party_reference_id_uk UNIQUE (org_party_id, reference_id)
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
CREATE TABLE consumer.order_segments
(
  id SERIAL,
  order_id INT NOT NULL,
  courier_party_id INT NOT NULL,
  shipping_type consumer.shipping_type NOT NULL,
  status consumer.order_status NOT NULL DEFAULT 'pending',
  currency_id INT NOT NULL,
  amount NUMERIC(14, 2) NOT NULL,
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
-- Table structure for boxes
--
CREATE TYPE consumer.box_status AS ENUM ('consolidated', 'paid', 'ready', 'in_transit', 'delivered', 'canceled');
CREATE TYPE consumer.weight_uom AS ENUM ('mg', 'g', 'kg', 'lb', 'oz', 'grain', 'ton', 'carat');
CREATE TYPE consumer.dimensions_uom AS ENUM ('mm', 'cm', 'dm', 'm', 'in', 'ft', 'yd', 'km', 'mile', 'nm');
CREATE TABLE consumer.boxes
(
  id SERIAL,
  quote_id INT,
  order_id INT,
  delivery_address_id INT NOT NULL,
  tracking_number VARCHAR(100) NOT NULL,
  status consumer.box_status NOT NULL DEFAULT 'consolidated',
  payment_method consumer.payment_method,
  payment_reference_id VARCHAR(100),
  weight NUMERIC(8, 2),
  weight_uom consumer.weight_uom,
  dimensions JSONB,
  dimensions_uom consumer.dimensions_uom,
  remarks TEXT,
  metadata JSONB,
  flagged SMALLINT NOT NULL DEFAULT 0,
  canceled_reason TEXT,
  consolidated_at TIMESTAMP WITH TIME ZONE,
  payment_due_at TIMESTAMP WITH TIME ZONE,
  paid_at TIMESTAMP WITH TIME ZONE,
  ready_at TIMESTAMP WITH TIME ZONE,
  in_transit_at TIMESTAMP WITH TIME ZONE,
  delivered_at TIMESTAMP WITH TIME ZONE,
  canceled_at TIMESTAMP WITH TIME ZONE,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT boxes_tracking_number_uk UNIQUE (tracking_number),
  -- Add the FK constraint after creating the quotes table.
  -- CONSTRAINT boxes_quote_id_fk FOREIGN KEY (quote_id) REFERENCES consumer.quotes ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT boxes_order_id_fk FOREIGN KEY (order_id) REFERENCES consumer.orders ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT boxes_delivery_address_id_fk FOREIGN KEY (delivery_address_id) REFERENCES core.addresses ON DELETE NO ACTION ON UPDATE NO ACTION
);

--
-- Table structure for quotes
--
CREATE TABLE consumer.quotes
(
  id SERIAL,
  box_id INT NOT NULL,
  currency_id INT NOT NULL,
  shipping_type consumer.shipping_type NOT NULL,
  subtotal NUMERIC(14, 2) NOT NULL,
  tax NUMERIC(14, 2) NOT NULL,
  fee NUMERIC(14, 2) NOT NULL,
  insurance NUMERIC(14, 2) NOT NULL,
  grand_total NUMERIC(14, 2) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT quotes_box_id_fk FOREIGN KEY (box_id) REFERENCES consumer.boxes (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT quotes_currency_id_fk FOREIGN KEY (currency_id) REFERENCES core.currencies (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
ALTER TABLE consumer.boxes ADD CONSTRAINT boxes_quote_id_fk FOREIGN KEY (quote_id) REFERENCES consumer.quotes ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Table structure for table bins
--
CREATE TABLE consumer.bins
(
  id SERIAL,
  org_party_id INT NOT NULL,
  bin_number VARCHAR(50) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT bins_org_party_id_fk FOREIGN KEY (org_party_id) REFERENCES core.organizations(party_id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT bins_bin_number_uk UNIQUE (bin_number)
);

--
-- Table structure for bin_items
--
CREATE TYPE consumer.bin_items_status AS ENUM ('received', 'expired', 'consolidated');
CREATE TABLE consumer.bin_items
(
  id SERIAL,
  bin_id INT NOT NULL,
  box_id INT,
  reference_id VARCHAR(100),
  shipping_type consumer.shipping_type,
  status consumer.bin_items_status NOT NULL DEFAULT 'received',
  currency_id INT NOT NULL,
  amount NUMERIC(14, 2) NOT NULL,
  quantity INT NOT NULL,
  total NUMERIC(14, 2) NOT NULL,
  merchant_name VARCHAR(255),
  description TEXT NOT NULL,
  courier_name VARCHAR(255),
  tracking_number VARCHAR(100),
  weight NUMERIC(8, 2),
  weight_uom consumer.weight_uom,
  dimensions JSONB,
  dimensions_uom consumer.dimensions_uom,
  remarks TEXT,
  metadata JSONB,
  flags JSONB,
  flagged SMALLINT NOT NULL DEFAULT 0,
  date_received TIMESTAMP WITH TIME ZONE NOT NULL,
  validity_date TIMESTAMP WITH TIME ZONE,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id),
  CONSTRAINT bin_items_bin_id_fk FOREIGN KEY (bin_id) REFERENCES consumer.bins (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT bin_items_box_id_fk FOREIGN KEY (box_id) REFERENCES consumer.boxes (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT bin_items_currency_id_fk FOREIGN KEY (currency_id) REFERENCES core.currencies (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
