<?php
class API {
	private $data_obj;
	private $user_id;
	private $outside_username = null;
	private $outside_password = null;

	public function __construct() {
		include ('data.php');
		$this->data_obj = new Data;

		// mod_php
		if (isset($_SERVER['PHP_AUTH_USER'])) {
		    $this->outside_username = $_SERVER['PHP_AUTH_USER'];
		    $this->outside_password = $_SERVER['PHP_AUTH_PW'];
		// most other servers
		} elseif (isset($_SERVER['HTTP_AUTHENTICATION'])) {
			if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']),'basic')===0) {
				list($this->outside_username,$this->outside_password) = explode(':',base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			}
		}

		// do a check here for the user, and check how many requests they've tried doing in the past hour
		if ($this->data_obj->check_user_exists($this->outside_username, $this->outside_password)==true) {
			// user exists
			// query the username for the user_id and return the user_id in the obj below
			$this->user_id = $this->data_obj->get_user_id_from_username($this->outside_username);
			// check the quota
			if ($this->data_obj->check_quota($this->user_id)!=true) {
				// user is over the limit
				$this->data_obj->send_response(429);
				die();
			}
		} else if ($this->data_obj->check_user_exists($this->outside_username, $this->outside_password)==false) {
			// user doesn't exist
			$this->data_obj->send_response(401);
			die();
		}

		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");
	}

    public function api_do() {
		// alright let's look for the first part of the string: 'users', then let's look to see if
		// there's a second param to check for 'articles', if there is, show articles by the user id,
		// example: users/1/articles, this would display all the articles by user 1
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        $paths = explode('/', $this->data_obj->paths($uri));

		// explode being strange giving "", we don't want that
		if (end($paths)=="") { array_pop($paths); }
		$original = $paths;

		// store paths to check if both exist
		$triple_resources = $paths;
		$quad_resources = $paths;
		@$pagination = $paths[3];

		array_shift($quad_resources);
		array_shift($quad_resources);
		array_shift($quad_resources);
		array_shift($quad_resources);
		array_shift($quad_resources);

        array_shift($paths); // Hack; get rid of initials empty string
        array_shift($paths); // Hack; shift again
		array_shift($paths); // Hack; shift again
		array_shift($paths); // Hack; shift again
		array_shift($paths); // Hack; shift again
		$resource = array_shift($paths);
		$id = array_shift($paths);

		array_shift($triple_resources); // Hack; shift again
		array_shift($triple_resources); // Hack; shift again
		array_shift($triple_resources); // Hack; shift again
		array_shift($triple_resources); // Hack; shift again
		array_shift($triple_resources); // Hack; shift again

		// we must first check for triple resources (users/id/articles), then if the double doesn't exist
		// we can go ahead and check for the singles

		// will return true if no elements are empty
		if ($this->data_obj->isEmpty($triple_resources)==true && count($triple_resources)==3 && $triple_resources[1]!="results"
		&& ($triple_resources[2] !="comments")) {
			$id = $triple_resources[1];

			// now we must query for the relationships because we know that the url is: (users/user_id/articles)
			unset($triple_resources[1]);
			// we now have 'users, articles that we can pass in table and split it up later for a join'
			$table = implode(', ', $triple_resources);

			array_splice($triple_resources, 1);
			array_shift($triple_resources);

			if (!empty($id)) {
				$this->handle_name($method, $id, $table);
			} else {
				// don't return anything as we're looking for relationships here..
				// send a http header response
				$this->data_obj->send_response(401);
			}
		} else if ($this->data_obj->isEmpty(@$triple_resources)==true && count(@$triple_resources)==3 &&
		end($triple_resources)=="comments") {
			// now we must query for the relationships because we know that the url is: (articles/article_id/comments)
			unset($triple_resources[1]);
			// we now have 'users, articles that we can pass in table and split it up later for a join'
			$table = implode(', ', $triple_resources);
			array_splice($triple_resources, 1);
			array_shift($triple_resources);

			if (!empty($id)) {
				$this->handle_name($method, $id, $table);
			} else {
				// don't return anything as we're looking for relationships here..
				// send a http header response
				$this->data_obj->send_response(401);
			}
		} else if ($this->data_obj->isEmpty(@$quad_resources)==true && count(@$quad_resources)==4 &&
		is_numeric(end($quad_resources)) && $quad_resources[2]=="comments") {
			// going to quad_resources we could get the following: (users/user_id/articles/article_id)
			$id = $quad_resources[1];
			$article_id = $quad_resources[3];

			unset($quad_resources[1]);
			unset($quad_resources[3]);
			$table = implode(', ', $quad_resources) . ", pagination_hmtr";

			if (!empty($id)) {
				// let's pass the article_id as value but for $method
				$this->handle_name($method, $id, $table, $article_id);
			} else {
				// don't return anything as we're looking for relationships here..
				// send a http header response
				$this->data_obj->send_response(400);
			}
		} else if ($resource == 'users') {
			$table = 'users';

			// let's check for pagination
			if (in_array("results", $original)) {
				// we must then get the users by pagination, start end
				// let's pass it into table
				$this->handle_base($method, $table.",".end($original));
			}

			if (empty($id)) {
				// no id so return all users
				$this->handle_base($method, $table);
			} else {
				if (is_numeric($id)) {
					$this->handle_name($method, $id, $table);
				} else {
					// put
					$username = end($original);
					$this->handle_name($method, $id, $table, $username);
				}
			}
		} else if ($resource == 'articles') {
			$table = 'articles';

			// let's check for pagination
			if (in_array("results", $original)) {
				// we must then get the users by pagination, start end
				// let's pass it into table
				$this->handle_base($method, $table.",".end($original));
			}

			if (empty($id)) {
				// no id so return all users
				$this->handle_base($method, $table);
			} else {
				if (is_numeric($id)) {
					$this->handle_name($method, $id, $table);
				}
			}
		} else if ($resource == 'comments') {
			$table = 'comments';

			// let's check for pagination
			if (in_array("results", $original)) {
				// we must then get the users by pagination, start end
				// let's pass it into table
				$this->handle_base($method, $table.",".end($original));
			}

			if (empty($id)) {
				// no id so return all users
				$this->handle_base($method, $table);
			} else {
				if (is_numeric($id)) {
					$this->handle_name($method, $id, $table);
				}
			}
		} else {
			$this->data_obj->send_response(404);
		}
    }

    private function handle_base($method, $table) {
        switch($method) {
	        case 'GET':
				@$users_table_limit = substr($table, 0, 5);
				@$users_limit = substr($table, 6);

				@$articles_table_limit = substr($table, 0, 8);
				@$articles_limit = substr($table, 9);

				@$comments_table_limit = substr($table, 0, 8);
				@$comments_limit = substr($table, 9);

				if ($table=="users") {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_all_users();
				} else if (@$users_table_limit=="users" && !empty($users_table_limit) && !empty($users_limit) && is_numeric(substr($users_limit, 0,1))) {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_users_limit($users_table_limit, $users_limit);
				} else if (@$articles_table_limit=="articles" && !empty($articles_table_limit) && !empty($articles_limit) && is_numeric(substr($articles_limit, 0,1))) {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_articles_limit($users_table_limit, $users_limit);
				} else if (@$comments_table_limit=="comments" && !empty($comments_table_limit) && !empty($comments_limit) && is_numeric(substr($comments_limit, 0,1))) {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_comments_limit($comments_table_limit, $comments_limit);
				} else if ($table=="articles") {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_all_articles();
				} else if ($table=="comments") {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_all_comments();
				}
	            break;
			case 'PUT':
				$this->data_obj->update_quota($this->outside_username);
				$this->edit($table, 0);
				break;
			case 'POST':
				$this->data_obj->update_quota($this->outside_username);
				$this->insert($table, 0);
				break;
			case 'DELETE':
				$this->data_obj->update_quota($this->outside_username);
				$this->delete($table);
				break;
	        default:
	            $this->data_obj->send_response(405);
	            break;
			}
    }

    private function handle_name($method, $id, $table, $value="") {
		$uri = $_SERVER['REQUEST_URI'];
		$paths = explode('/', $this->data_obj->paths($uri));

        switch ($method) {
	        case 'POST':
	            $this->data_obj->update_quota($this->outside_username);
				$this->insert($table, $value);
	            break;
			case 'PUT':
				$this->data_obj->update_quota($this->outside_username);
				$this->edit($table, $value);
				break;
	        case 'DELETE':
				$this->data_obj->update_quota($this->outside_username);
	            $this->delete($table);
	            break;
	        case 'GET':
				if ($table == "users" && !empty($id) && !in_array("results", $paths)) {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_user($id);
				} else if ($table == "articles" && !empty($id)) {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_article($id);
				} else if ($table == "comments" && !empty($id)) {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_comment($id);
				} else if ($table == "users, articles") {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_users_articles($id, $table);
				} else if ($table == "users, articles, pagination_hmtr") {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_pagination_hmtr_by_user($id, $table, $value);
				} else if ($table == "articles, comments") {
					$this->data_obj->update_quota($this->outside_username);
					$this->data_obj->get_article_comments($id, $table);
				}
	            break;
	        default:
				$this->data_obj->send_response(405);
				break;
	        }
    }

	public function edit($table, $value) {
		$data = json_decode(file_get_contents('php://input'));

		if ($table=="users") {
			if (empty($data->username)) {
				$this->data_object->send_response(400);
			} else {
				if (!empty($data->first_name)) {
					$sql = "UPDATE users SET first_name = :first_name WHERE username = :username";
					$stmt = $this->data_obj->dbc->prepare($sql);

					$stmt->bindParam('username', $data->username);
					$stmt->bindParam("first_name", $data->first_name);
					$count = $stmt->execute();
				}

				if (!empty($data->last_name)) {
					$sql = "UPDATE users SET last_name = :last_name WHERE username = :username";
					$stmt = $this->data_obj->dbc->prepare($sql);

					$stmt->bindParam('username', $data->username);
					$stmt->bindParam("last_name", $data->last_name);
					$count = $stmt->execute();
				}

				if (!empty($data->email_address)) {
					$sql = "UPDATE users SET email_address = :email_address WHERE username = :username";
					$stmt = $this->data_obj->dbc->prepare($sql);

					$stmt->bindParam('username', $data->username);
					$stmt->bindParam("email_address", $data->email_address);
					$count = $stmt->execute();
				}

				// row has been updated, therefore give back 200
				if ($count>=1) {
					$user_id = $this->data_obj->get_user_id_from_users_table($data->username);

					$result['success']="true";
					$result['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$user_id;

					$this->data_obj->send_response(200, json_encode($result));
				} else {
					$this->data_obj->send_response(400);
				}
			}
		} else if ($table=="articles") {
			if (empty($data->article_id)) {
				$this->data_object->send_response(400);
			} else {
				if ($table=="articles") {
					// check article_title, article_sub_title, article_content isn't empty from $data
					if (!empty($data->article_title)) {
						$sql = "UPDATE articles SET article_title = :article_title WHERE article_id = :article_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('article_title', $data->article_title);
						$stmt->bindParam("article_id", $data->article_id);
						$count = $stmt->execute();
					}

					if (!empty($data->article_sub_title)) {
						$sql = "UPDATE articles SET article_sub_title = :article_sub_title WHERE article_id = :article_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('article_sub_title', $data->article_sub_title);
						$stmt->bindParam("article_id", $data->article_id);
						$count = $stmt->execute();
					}

					if (!empty($data->article_content)) {
						$sql = "UPDATE articles SET article_content = :article_content WHERE article_id = :article_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('article_content', $data->article_content);
						$stmt->bindParam("article_id", $data->article_id);
						$count = $stmt->execute();
					}

					if (!empty($data->article_views)) {
						$sql = "UPDATE articles SET article_views = :article_views WHERE article_id = :article_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('article_views', $data->article_views);
						$stmt->bindParam("article_id", $data->article_id);
						$count = $stmt->execute();
					}

					if (!empty($data->article_unique_views)) {
						$sql = "UPDATE articles SET article_unique_views = :article_unique_views WHERE article_id = :article_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('article_views', $data->article_unique_views);
						$stmt->bindParam("article_id", $data->article_id);
						$count = $stmt->execute();
					}

					if (!empty($data->article_author_id)) {
						$sql = "UPDATE articles SET article_author_id = :article_author_id WHERE article_id = :article_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('article_author_id', $data->article_author_id);
						$stmt->bindParam("article_id", $data->article_id);
						$count = $stmt->execute();
					}

					// row has been updated, therefore give back 200
					if ($count>=1) {
						$result['success']="true";
						$result['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/articles/".$data->article_id;

						$this->data_obj->send_response(200, json_encode($result));
					} else {
						$this->data_obj->send_response(400);
					}
				}
			}
		} else if ($table=="comments") {
			if (empty($data->comment_id)) {
				$this->data_object->send_response(400);
			} else {
				if ($table=="comments") {
					// check comment_full_name, comment_full, comment_location isn't empty from $data
					if (!empty($data->comment_full_name)) {
						$sql = "UPDATE comments SET comment_full_name = :comment_full_name WHERE comment_id = :comment_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('comment_full_name', $data->comment_full_name);
						$stmt->bindParam("comment_id", $data->comment_id);
						$count = $stmt->execute();
					}

					if (!empty($data->comment_full)) {
						$sql = "UPDATE comments SET comment_full = :comment_full WHERE comment_id = :comment_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('comment_full', $data->comment_full);
						$stmt->bindParam("comment_id", $data->comment_id);
						$count = $stmt->execute();
					}

					if (!empty($data->comment_location)) {
						$sql = "UPDATE comments SET comment_location = :comment_location WHERE comment_id = :comment_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('comment_location', $data->comment_location);
						$stmt->bindParam("comment_id", $data->comment_id);
						$count = $stmt->execute();
					}

					if (!empty($data->comment_likes)) {
						$sql = "UPDATE comments SET comment_likes = :comment_likes WHERE comment_id = :comment_id";
						$stmt = $this->data_obj->dbc->prepare($sql);

						$stmt->bindParam('comment_likes', $data->comment_likes);
						$stmt->bindParam("comment_id", $data->comment_id);
						$count = $stmt->execute();
					}
				}

				// row has been updated, therefore give back 200
				if ($count>=1) {
					$result['success']="true";
					$result['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/comments/".$data->comment_id;

					$this->data_obj->send_response(200, json_encode($result));
				} else {
					$this->data_obj->send_response(400);
				}
			}
		}
	}

	public function insert($table, $value) {
        $data = json_decode(file_get_contents('php://input'));

		if ($table=="comments" && empty($data->comment_article_id)) {
			$this->data_obj->send_response(400);
		} else if ($table=="comments" && is_numeric($data->comment_article_id) && !empty($data->comment_article_id)) {
			if (empty($data->comment_full_name)) {
				$data->comment_full_name = "";
			}
			if (empty($data->comment_full)) {
				$data->comment_full = "";
			}
			if (empty($data->comment_location)) {
				$data->comment_location = "";
			}
			if (empty($data->comment_likes)) {
				$data->comment_likes = 0;
			}

			$latest_comment_id = $this->data_obj->get_latest_comment_id();

			$sql = "INSERT INTO comments (comment_id, comment_full_name, comment_full, comment_location, comment_likes, comment_article_id)
			VALUES (:comment_id, :comment_full_name, :comment_full, :comment_location, :comment_likes, :comment_article_id)";
			$stmt = $this->data_obj->dbc->prepare($sql);

			$stmt->bindParam('comment_id', $latest_comment_id);
			$stmt->bindParam('comment_article_id', $data->comment_article_id);
			$stmt->bindParam('comment_full_name', $data->comment_full_name);
			$stmt->bindParam("comment_full", $data->comment_full);
			$stmt->bindParam("comment_location", $data->comment_location);
			$stmt->bindParam("comment_likes", $data->comment_likes);

			// if success insert, return 201
			if ($stmt->execute()==true) {
				$result['success']="true";
				$result['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/comments/".$latest_comment_id;

				$this->data_obj->send_response(201, json_encode($result));
			} else {
				// if something went wrong
				$this->data_obj->send_response(409);
			}
		} else {
			$this->data_obj->send_response(400);
		}

		if ($table=="articles" && empty($data->article_title)) {
			$this->data_obj->send_response(400);
		} else if ($table=="articles" && !empty($data->article_title)) {
			if (empty($data->article_title)) {
				$data->article_title = "";
			}

			if (empty($data->article_sub_title)) {
				$data->article_sub_title = "";
			}

			if (empty($data->article_content)) {
				$data->article_content = "";
			}

			if (empty($data->article_views)) {
				$data->article_views = 0;
			}

			if (empty($data->article_unique_views)) {
				$data->article_unique_views = 0;
			}

			if (empty($data->article_author_id)) {
				$data->article_author_id = 0;
			}

			if (empty($data->article_post_date)) {
				$data->article_post_date = "";
			}

			$latest_article_id = $this->data_obj->get_article_id_latest();

			$sql = "INSERT INTO articles (article_id, article_title, article_sub_title, article_content, article_views, article_unique_views, article_author_id, article_post_date)
			VALUES (:article_id, :article_title, :article_sub_title, :article_content, :article_views, :article_unique_views, :article_author_id, :article_post_date)";
			$stmt = $this->data_obj->dbc->prepare($sql);

			// if these are empty it will just insert empty values, which later can be updated using put
			$stmt->bindParam('article_id', $latest_article_id);
			$stmt->bindParam('article_title', $data->article_title);
			$stmt->bindParam('article_sub_title', $data->article_sub_title);
			$stmt->bindParam("article_content", $data->article_content);
			$stmt->bindParam("article_views", $data->article_views);
			$stmt->bindParam("article_unique_views", $data->article_unique_views);
			$stmt->bindParam("article_author_id", $data->article_author_id);
			$stmt->bindParam("article_post_date", $data->article_post_date);

			// if success insert, return 201
			if ($stmt->execute()==true) {
				// HATEOAS
				$result['success']="true";
				$result['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/articles/".$latest_article_id;

				$this->data_obj->send_response(201, json_encode($result));
			} else {
				// if something went wrong
				$this->data_obj->send_response(409);
			}
		}

		// if the username is empty for users, send back a 400
		if ($table=="users" && empty($data->username)) {
            $this->data_obj->send_response(400);
    	} else if ($table=="users" && !empty($data->username)) {
			if (empty($data->first_name)) {
				$data->first_name = "";
			}
			if (empty($data->last_name)) {
				$data->last_name = "";
			}
			if (empty($data->email_address)) {
				$data->email_address = "";
			}
			// check for same email_address or username, else insert
			$sql = "SELECT email_address, username FROM users WHERE email_address = :email_address OR username = :username";
			$stmt = $this->data_obj->dbc->prepare($sql);
			$stmt->bindParam('email_address', $data->email_address);
			$stmt->bindParam('username', $data->username);

			$stmt->execute();
			$result = $stmt->fetch(\PDO::FETCH_OBJ);
			$num_rows = $stmt->rowCount();

			// if empty result and email_address isn't found, go ahead and prepare to insert
			if ($num_rows<=0) {
				// let's insert because there's no duplicate email_address
				try {
					$sql = "INSERT INTO users (user_id, username, first_name, last_name, email_address) VALUES (:user_id, :username, :first_name,
					:last_name, :email_address)";

					$stmt = $this->data_obj->dbc->prepare($sql);

					$last_id = $this->data_obj->get_user_id_latest();

					// if these are empty it will just insert empty values, which later can be updated using put
					$stmt->bindParam('user_id', $last_id);
					$stmt->bindParam('username', $data->username);
					$stmt->bindParam("first_name", $data->first_name);
					$stmt->bindParam("last_name", $data->last_name);
					$stmt->bindParam("email_address", $data->email_address);

					// if success insert, return 201
					if ($stmt->execute()==true) {
						// HATEOAS
						$result['success']="true";
						$result['href']="http://fostvm.fost.plymouth.ac.uk/modules/soft338/khadwen/v1/users/".$last_id;

						$this->data_obj->send_response(201, json_encode($result));
					} else {
						// if something went wrong
						$this->data_obj->send_response(409);
					}
				} catch (\PDOException $e) {
					$this->data_obj->send_response(400);
				}
			} else {
				// send 409 back if duplicate
				$this->data_obj->send_response(409);
			}
        }
	}

    private function delete($table) {
		try {
			$data = json_decode(file_get_contents('php://input'));

			if ($table=="users") {
				// we need a username to delete a user
				$username = $data->username;

				$sql = "SELECT username FROM users where username = :username";
				$stmt = $this->data_obj->dbc->prepare($sql);
				$stmt->bindParam('username', $username);
				$stmt->execute();

				$result = $stmt->fetch(\PDO::FETCH_OBJ);
				$num_rows = $stmt->rowCount();

				if ($num_rows==1) {
					// user is there
					$result=array();
					$result['success']="true";

					$sql = "DELETE FROM users WHERE username = :username";
					$stmt = $this->data_obj->dbc->prepare($sql);
					$stmt->bindParam('username', $username);
					$deleted = $stmt->execute();

					if ($deleted==1) {
						$this->data_obj->send_response(200, json_encode($result));
					} else {
						$this->data_obj->send_response(409);
					}
				} else {
					$this->data_obj->send_response(409);
				}
			} else if ($table=="comments") {
				// we need a comment id to delete a comment
				$sql = "SELECT comment_id FROM comments where comment_id = :comment_id";
				$stmt = $this->data_obj->dbc->prepare($sql);

				$stmt->bindParam('comment_id', $data->comment_id);
				$stmt->execute();

				$num_rows = $stmt->rowCount();

				if ($num_rows==1) {
					// comment is there at that id
					$result=array();
					$result['success']="true";

					$sql = "DELETE FROM comments WHERE comment_id = :comment_id";
					$stmt = $this->data_obj->dbc->prepare($sql);

					$stmt->bindParam('comment_id', $data->comment_id);
					$deleted = $stmt->execute();

					if ($deleted==1) {
						$this->data_obj->send_response(200, json_encode($result));
					} else {
						$this->data_obj->send_response(409);
					}
				} else {
					$this->data_obj->send_response(409);
				}
			} else if ($table=="articles") {
				// we need an article id to delete an article
				// we need a comment id to delete a comment
				$sql = "SELECT article_id FROM articles where article_id = :article_id";
				$stmt = $this->data_obj->dbc->prepare($sql);

				$stmt->bindParam('article_id', $data->article_id);
				$stmt->execute();

				$num_rows = $stmt->rowCount();

				if ($num_rows==1) {
					$result=array();
					$result['success']="true";

					// comment is there at that id
					$sql = "DELETE FROM articles WHERE article_id = :article_id";
					$stmt = $this->data_obj->dbc->prepare($sql);

					$stmt->bindParam('article_id', $data->article_id);
					$deleted = $stmt->execute();

					if ($deleted==1) {
						$this->data_obj->send_response(200, json_encode($result));
					} else {
						$this->data_obj->send_response(409);
					}
				} else {
					$this->data_obj->send_response(409);
				}
			}
		} catch (\PDOException $e) {
			$this->data_obj->send_response(404);
		}
    }
}
$api = new API;
$api->api_do();
?>
