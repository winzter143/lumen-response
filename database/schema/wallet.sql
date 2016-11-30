DROP SCHEMA IF EXISTS wallet;
CREATE SCHEMA wallet;

--
-- Table structure for table wallets
--
CREATE TYPE wallet."wallets_enum_type" as enum('fund', 'sales', 'settlement', 'collections');
CREATE TABLE wallet."wallets" (
  "id" serial ,
  "party_id" integer NOT NULL ,
  "type" wallet."wallets_enum_type" NOT NULL ,
  "currency_id" integer NOT NULL ,
  "amount" numeric(14,2) NOT NULL  DEFAULT 0.00,
  "credit_limit" numeric(14,2) DEFAULT 0.00,
  "max_limit" numeric(14,2) DEFAULT 0.00,
  "status" smallint NOT NULL  DEFAULT 1,
  "created_by" integer ,
  "created_at" timestamp with time zone NOT NULL DEFAULT current_timestamp ,
  "updated_by" integer ,
  "updated_at" timestamp with time zone ,
  PRIMARY KEY ("id") ,
  CONSTRAINT wallets_currency_id_fk FOREIGN KEY (currency_id) REFERENCES core.currencies (id) ON DELETE NO ACTION ON UPDATE NO ACTION ,
  CONSTRAINT wallets_party_id_fk FOREIGN KEY (party_id) REFERENCES core.parties (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
CREATE UNIQUE INDEX "wallets_idx_wallets_party_id_type_currency_id_uk" ON wallet."wallets" ("party_id","type","currency_id");
CREATE INDEX "wallets_idx_wallets_party_id" ON wallet."wallets" ("party_id");
CREATE INDEX "wallets_idx_wallets_type" ON wallet."wallets" ("type");
CREATE INDEX "wallets_idx_wallets_currency_id" ON wallet."wallets" ("currency_id");

--
-- Table structure for table transfers
--
CREATE TYPE wallet."transfers_enum_type" as enum('purchase','transfer','refund','reward','escrow','disbursement','settlement','sale','fund');
CREATE TABLE wallet."transfers" (
  "id" serial ,
  "from_wallet_id" integer NOT NULL ,
  "to_wallet_id" integer NOT NULL ,
  "type" wallet."transfers_enum_type" NOT NULL ,
  "amount" numeric(14,2) NOT NULL ,
  "details" text NOT NULL ,
  "ip_address" text ,
  "order_id" integer ,
  "created_by" integer ,
  "created_at" timestamp with time zone NOT NULL DEFAULT current_timestamp ,
  PRIMARY KEY ("id") ,
  CONSTRAINT transfers_from_wallet_id_fk FOREIGN KEY (from_wallet_id) REFERENCES wallet.wallets (id) ON DELETE NO ACTION ON UPDATE NO ACTION ,
  CONSTRAINT transfers_to_wallet_id_fk FOREIGN KEY (to_wallet_id) REFERENCES wallet.wallets (id) ON DELETE NO ACTION ON UPDATE NO ACTION ,
  CONSTRAINT transfers_order_id_fk FOREIGN KEY (order_id) REFERENCES consumer.orders (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
CREATE INDEX "transfers_idx_transfers_type" ON wallet."transfers" ("type");
CREATE INDEX "transfers_idx_transfers_from_wallet_id" ON wallet."transfers" ("from_wallet_id");
CREATE INDEX "transfers_idx_transfers_to_wallet_id" ON wallet."transfers" ("to_wallet_id");
CREATE INDEX "transfers_idx_transfers_created_at" ON wallet."transfers" ("created_at");

--
-- Table structure for table wallet_logs
--
CREATE TABLE wallet."wallet_logs" (
  "id" serial ,
  "wallet_id" integer NOT NULL ,
  "transfer_id" integer NOT NULL ,
  "amount" numeric(14,2) NOT NULL ,
  "running_balance" numeric(14,2) NOT NULL ,
  PRIMARY KEY ("id") ,
  CONSTRAINT wallet_logs_wallet_id_fk FOREIGN KEY (wallet_id) REFERENCES wallet.wallets (id) ON DELETE NO ACTION ON UPDATE NO ACTION ,
  CONSTRAINT wallet_logs_transfer_id_fk FOREIGN KEY (transfer_id) REFERENCES wallet.transfers (id) ON DELETE NO ACTION ON UPDATE NO ACTION
);
CREATE UNIQUE INDEX "wallet_logs_idx_wallet_logs_wallet_id_transfer_id_uk" ON wallet."wallet_logs" ("wallet_id","transfer_id");
CREATE INDEX "wallet_logs_idx_wallet_logs_wallet_id" ON wallet."wallet_logs" ("wallet_id");
CREATE INDEX "wallet_logs_idx_wallet_logs_transfer_id" ON wallet."wallet_logs" ("transfer_id");
