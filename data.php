<?php
class Data {
	public $dbc;

	private $username;
	private $password;
	private $quota = "1000";

	// constructor - open DB connection
	public function __construct() {
		try {
			$this->dbc = new PDO('mysql:host=localhost;dbname=khadwen', $db_username="khadwen", $db_password="null");
			$this->dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			$this->send_response(500);
		}
	}

	public function __destruct() {
		$this->dbc = null;
	}

	public function isEmpty($array) {
		@$array = array();
		foreach ($array as $element) {
			if ($element=="") {
				// element is empty
				return false;
			}
		}
		// if no elements are empty
		return true;
	}

	public function get_user_id_from_username($username) {
		try {
			$sql = "SELECT api_user_id, api_username, api_password FROM api_users WHERE api_username = :username";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('username', $username);

			$stmt->execute();
			$results = $stmt->fetch();

			$user_id = $results['api_user_id'];

			return $user_id;
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_user_id_from_users_table($username) {
		try {
			$sql = "SELECT user_id, username FROM users WHERE username = :username";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('username', $username);

			$stmt->execute();
			$results = $stmt->fetch();

			$user_id = $results['user_id'];

			return $user_id;
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_latest_comment_id() {
		try {
			$sql = "SELECT comment_id FROM comments ORDER BY comment_id DESC LIMIT 1";
			$stmt = $this->dbc->prepare($sql);

			$stmt->execute();
			$results = $stmt->fetch();

			return $results[0]+1;
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_article_id_latest() {
		try {
			$sql = "SELECT article_id FROM articles ORDER BY article_id DESC LIMIT 1";
			$stmt = $this->dbc->prepare($sql);

			$stmt->execute();
			$results = $stmt->fetch();

			return $results[0]+1;
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_user_id_latest() {
		try {
			$sql = "SELECT user_id FROM users ORDER BY user_id DESC LIMIT 1";
			$stmt = $this->dbc->prepare($sql);

			$stmt->execute();
			$results = $stmt->fetch();

			return $results[0]+1;
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function update_quota($username) {
		// check if the date (today) is in the database for the user, if it's not, add it
		try {
			$user_id = $this->get_user_id_from_username($username);

			// check if date is in there for today
			$sql = "SELECT quota_id, user_id, date, quota FROM quota WHERE user_id = :user_id";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('user_id', $user_id);

			$stmt->execute(array($user_id));
			$results = $stmt->fetch();

			$num_rows = $stmt->rowCount();

			if ($num_rows==0) {
				// no date, let's add it
				$sql = "INSERT INTO quota (date, user_id) VALUES (CURDATE(), :user_id)";
				$stmt = $this->dbc->prepare($sql);
				$stmt->bindParam('user_id', $user_id);
				$stmt->execute();
			}

			$sql = "UPDATE quota SET user_id = :user_id, quota = quota + 1 WHERE user_id = :user_id";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('user_id', $user_id);

			$stmt->execute();
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function check_quota($user_id) {
		try {
			$sql = "SELECT quota_id, user_id, date, quota FROM quota
			WHERE date = CURDATE() AND user_id = :user_id";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('user_id', $user_id);

			$stmt->execute();
			$results = $stmt->fetch();

			$num_rows = $stmt->rowCount();

			if ($num_rows==1) {
				// user/data exists
				// date exists, next let's check that the quota isn't over 1000
				if ($results['quota']>1000) {
					$this->send_response(429);
					die();
				}
				// if it doesn't exist, it should be fine as the user hasn't used the api today
			}
			return true;
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

    public function get_users_articles($id, $table) {
		// split the table because it will come in as 'users, articles'
		try {
			$sql = "SELECT users.user_id, users.username, users.first_name, users.last_name, users.email_address,
			article_id, article_title, article_sub_title, article_content, article_views,
			article_unique_views, article_author_id, article_post_date
			FROM articles INNER JOIN users ON users.user_id = articles.article_author_id
			WHERE users.user_id = :id
			ORDER BY article_id DESC
			LIMIT 100";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('id', $id);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (!empty($results)) {
				// HATEOAS
				for ($counter=0; $counter<count($results); $counter++) {
					$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$results[$counter]['user_id'];
				}
				$this->send_response(200, json_encode($results));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_article_comments($id, $table) {
		// split the table because it will come in as 'users, articles'
		try {
			$sql = "SELECT article_id, comment_id, comment_article_id, comment_full_name, comment_full, comment_location,
			comment_likes
			FROM comments INNER JOIN articles ON comment_article_id = article_id
			WHERE article_id = :id ORDER BY article_id DESC LIMIT 100";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('id', $id);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (!empty($results)) {
				// HATEOAS
				for ($counter=0; $counter<count($results); $counter++) {
					$results[$counter]['article_id']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/article_id/".$results[$counter]['article_id'];
					$results[$counter]['comment_id']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/article_id/".$results[$counter]['comment_id'];
				}
				$this->send_response(200, json_encode($results));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_pagination_hmtr_by_user($id, $table, $value) {
		// split the table because it will come in as 'users, articles, pagination_hmtr'
		try {
			$sql = "SELECT users.user_id, users.username, users.first_name, users.last_name, users.email_address,
			article_id, article_title, article_sub_title, article_content, article_views,
			article_unique_views, article_author_id, article_post_date
			FROM articles INNER JOIN users ON users.user_id = articles.article_author_id
			WHERE users.user_id = :id
			LIMIT 0, :value";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('id', $id);
			$stmt->bindParam('value', $value);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_rows = $stmt->rowCount();

			// value is out of range, therefore we return a 400 because it doesn't exist in the db
			if ($value > $num_rows) {
				$this->send_response(400);
			} else if (!empty($results)) {
				// HATEOAS
				for ($counter=0; $counter<count($results); $counter++) {
					$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$results[$counter]['user_id'];
				}
				$this->send_response(200, json_encode($results));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_users_limit($table, $value) {
		try {
			$get_range = explode(",", $value);
			$limit_start = $get_range[0];
			$limit_end = $get_range[1];

			$sql = "SELECT * FROM users ORDER BY user_id DESC LIMIT :limit_start, :limit_end";
			$stmt = $this->dbc->prepare($sql);

			$stmt->bindValue(':limit_start', (int) trim($get_range[0]), PDO::PARAM_INT);
			$stmt->bindValue(':limit_end', (int) trim($get_range[1]), PDO::PARAM_INT);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_rows = $stmt->rowCount();

			if (!empty($results)) {
				if ($limit_end > $num_rows) {
					$this->send_response(200);
				} else {
					// HATEOAS
					for ($counter=0; $counter<count($results); $counter++) {
						$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$results[$counter]['user_id'];
					}

					$this->send_response(200, json_encode($results));
				}
			} else {
				$this->send_response(200);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_articles_limit($table, $value) {
		$get_range = explode(",", $value);
		array_shift($get_range);

		try {
			$sql = "SELECT * FROM articles ORDER BY article_id DESC LIMIT :limit_start, :limit_end";
			$stmt = $this->dbc->prepare($sql);

			@$limit_start = $get_range[0];
			@$limit_end = $get_range[1];

			$stmt->bindValue(':limit_start', (int) trim($get_range[0]), PDO::PARAM_INT);
			$stmt->bindValue(':limit_end', (int) trim($get_range[1]), PDO::PARAM_INT);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_rows = $stmt->rowCount();

			if (!empty($results)) {
				if ($limit_end > $num_rows) {
					$this->send_response(400);
				} else {
					// HATEOAS
					for ($counter=0; $counter<count($results); $counter++) {
						$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/articles/".$results[$counter]['article_id'];
					}

					$this->send_response(200, json_encode($results));
				}
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_comments_limit($table, $value) {
		$get_range = explode(",", $value);

		try {
			@$limit_start = $get_range[0];
			@$limit_end = $get_range[1];

			$sql = "SELECT * FROM comments ORDER BY comment_id  DESC LIMIT :limit_start, :limit_end";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindValue(':limit_start', (int) trim($get_range[0]), PDO::PARAM_INT);
			$stmt->bindValue(':limit_end', (int) trim($get_range[1]), PDO::PARAM_INT);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_rows = $stmt->rowCount();

			if (!empty($results)) {
				if ($limit_end > $num_rows) {
					$this->send_response(400);
				} else {
					// HATEOAS
					for ($counter=0; $counter<count($results); $counter++) {
						$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/comments/".$results[$counter]['comment_id'];
					}
					$this->send_response(200, json_encode($results));
				}
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_all_users() {
		try {
			$sql = "SELECT * FROM users ORDER BY user_id DESC LIMIT 100";
			$stmt = $this->dbc->prepare($sql);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (!empty($results)) {
				// HATEOAS
				for ($counter=0; $counter<count($results); $counter++) {
					$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$results[$counter]['user_id'];
				}

				$this->send_response(200, json_encode($results));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function check_user_exists($username, $password) {
		try {
			$sql = "SELECT api_username, api_password FROM api_users WHERE api_username = :username AND api_password = :password";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam('username', $username);
			$stmt->bindParam('password', $password);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$num_rows = $stmt->rowCount();

			if ($num_rows==1) {
				return true;
			} else {
				return false;
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_all_comments() {
		try {
			$sql = "SELECT * FROM comments ORDER BY comment_id DESC LIMIT 100";
			$stmt = $this->dbc->prepare($sql);

			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (!empty($results)) {
				// HATEOAS
				for ($counter=0; $counter<count($results); $counter++) {
					$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/comments/".$results[$counter]['comment_id'];
				}

				$this->send_response(200, json_encode($results));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_all_articles() {
		try {
			$sql = "SELECT * FROM articles ORDER BY article_id DESC LIMIT 100";
			$stmt = $this->dbc->prepare($sql);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (!empty($results)) {
				// HATEOAS
				for ($counter=0; $counter<count($results); $counter++) {
					$results[$counter]['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/articles/".$results[$counter]['article_id'];
				}

				$this->send_response(200, json_encode($results));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_user($id) {
		try {
			$sql = "SELECT * FROM users where user_id = :id";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam("id", $id);
			$stmt->execute();
			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (!empty($result)) {
				// HATEOAS
				$result->href="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$id;

				$this->send_response(200, json_encode($result));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_comment($id) {
		try {
			$sql = "SELECT * FROM comments WHERE comment_id = :id";
			$stmt = $this->dbc->prepare($sql);

			$stmt->bindValue(':id', (int) trim($id), PDO::PARAM_INT);
			$stmt->execute();
			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (!empty($result)) {
				// HATEOAS
				$result->href="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$id;

				$this->send_response(200, json_encode($result));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function get_article($id) {
		try {
			$sql = "SELECT * FROM articles where article_id = :id";
			$stmt = $this->dbc->prepare($sql);
			$stmt->bindParam("id", $id);
			$stmt->execute();
			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (!empty($result)) {
				// HATEOAS
				$result->href="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/articles/".$result->article_id;

				$this->send_response(200, json_encode($result));
			} else {
				$this->send_response(400);
			}
		} catch (\PDOException $e) {
			$this->send_response(400);
		}
	}

	public function paths($url) {
		$uri = parse_url($url);
		return $uri['path'];
	}

	public static function get_status_code_message($status) {
		$codes = Array (
			200 => 'OK',
			201 => 'Created',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			409 => 'Conflict',
			429 => 'Too Many Requests',
			500 => 'Internal Server Error'
		);

		return (isset($codes[$status])) ? $codes[$status] : '';
	}

	// helper method to send a HTTP response code/message
	public function send_response($status = 200, $body = '', $content_type = 'text/html') {
		$status_header = 'HTTP/1.1 ' . $status . ' ' . $this->get_status_code_message($status);
		header($status_header);
		header('Content-type: ' . $content_type);
		echo $body;
	}
}
?>
