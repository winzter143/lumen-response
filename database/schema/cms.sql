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
  parent_id INT,
  slug VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  content JSONB,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT pages_slug_uk UNIQUE (slug),
  CONSTRAINT pages_name_uk UNIQUE (name)
);
