DROP SCHEMA IF EXISTS cms;
CREATE SCHEMA cms;

CREATE TYPE cms.page_assets_type AS ENUM('carousel', 'grid');

CREATE TABLE cms.assets
(
  id SERIAL,
  source VARCHAR(255),
  full_image VARCHAR(255),
  thumb_small VARCHAR(255),
  thumb_medium VARCHAR(255),
  thumb_large VARCHAR(255),
  filesize INT,
  mimetype VARCHAR(100) NOT NULL,
  caption VARCHAR(255),
  content TEXT,
  created_by INT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified_by INT,
  modified_at  TIMESTAMP WITH TIME ZONE,
  PRIMARY KEY (id)
);

CREATE INDEX assets_caption_content_idx ON cms.assets USING gin(to_tsvector('english', content));

CREATE TABLE cms.pages
(
  id SERIAL,
  slug VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT pages_slug_uk UNIQUE (slug),
  CONSTRAINT pages_name_uk UNIQUE (name)
);

CREATE TABLE cms.page_assets
(
  id SERIAL,
  page_id INT NOT NULL,
  asset_id INT NOT NULL,
  type cms.page_assets_type NOT NULL,
  sort_order SMALLINT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT page_assets_page_id_fk FOREIGN KEY (page_id) REFERENCES cms.pages (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT page_assets_asset_id_fk FOREIGN KEY (asset_id) REFERENCES cms.assets (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT page_assets_page_id_asset_id_uk UNIQUE (page_id, asset_id)
);
