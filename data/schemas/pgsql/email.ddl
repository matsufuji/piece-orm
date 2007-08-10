-- $Id$

CREATE TABLE email (
  id serial,
  email varchar(255) NOT NULL,
  version int4 NOT NULL DEFAULT '0',
  rdate timestamp with time zone NOT NULL DEFAULT current_timestamp,
  mdate timestamp with time zone NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY(id),
  UNIQUE(email)
);

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */