
-- The ID fields are text because they could
-- contain non-numeric characters.

CREATE TABLE fb_likenotifications (
  user_id text NOT NULL,
  access_token text NOT NULL,
  user_email text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE fb_likewho (
  user_id text NOT NULL,
  who_likes_id text NOT NULL,
  who_likes_name text NOT NULL,
  post_id text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

