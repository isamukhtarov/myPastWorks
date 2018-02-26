<?php
/**
 * Created by PhpStorm.
 * User: Isa
 * Date: 15.12.17
 * Time: 22:34
 */

trait ClassesTrait {
	public function get_page ( $lang, $id = 0 ) {
		if ( $id == 0 ) {
			$id = $this->secure_check_string ( $_GET[ 'id' ] );
		}

		$q = $this->query ( "SELECT * FROM pages WHERE id='{$id}'" );

		if ( $this->numRows ( $q ) == 0 ) {
			echo "<script>location.href='/';</script>";
			exit;
		}
		$s = $this->fetch ( $q );

		$imagesHtml = '';

//
		$title = $s[ 'title_' . $lang ];
		$content = $s[ 'content_' . $lang ];
		$image1 = $s[ 'image1' ];
		$image2 = $s[ 'image2' ];
		$images1 = explode ( ',', $s[ 'images1' ] );

		foreach ( $images1 as $value ) {
			$imagesHtml .= <<<HTML
                <div class="grid-item grid-item-img">
                    <div class="images-block">
                      <a class="magnific-gallery" href="/{$value}"><img src="/{$value}"></a>
                    </div>
                </div>
HTML;

		}

		$array = Array ( 'title' => $title, 'content' => $content, 'image1' => $image1, 'image2' => $image2, 'images1' => $imagesHtml );

		return $array;
	}


	public function getAllExhibitions ( $lang, $catId ) {
		$result = '';
		$sql = "SELECT `id`, `image1`, `title_{$lang}` `title`, `date_from`, `date_to`, DATE_FORMAT(`date_from`,'%d') `day_from`, DATE_FORMAT(`date_to`,'%d') `day_to`, DATE_FORMAT(`date_to`,'%Y') `year` FROM `exhibitions` WHERE `category` = '{$catId}' ORDER BY `date_from` DESC";

		$per_page = $this->per_page;
		if ( isset( $_GET[ 'page' ] ) ) {
			$page = (int)$this->secure_check_string ( $_GET[ 'page' ] );
		} else {
			$page = 1;
		}

		$pagination = $this->generate_pagination ( $sql, $per_page, $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] );

		$limits = $this->generate_limit ( $page, $per_page );
		$sql = $sql . ' LIMIT ' . $limits;

		$query = $this->query ( $sql );
		while ( $q = $this->fetch ( $query ) ) {
			$id = $q [ 'id' ];
			$image = $q [ 'image1' ];
			$type = $this->getCurrentExhibitionType ( $lang, $id );
			$monthFrom = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_from' ] ) ) );
			$monthTo = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_to' ] ) ) );

			$dayFrom = $q [ 'day_from' ];
			$dayTo = $q [ 'day_to' ];
			$year = $q [ 'year' ];

			$title = mb_substr ( $q [ 'title' ], 0, 60 ) . '...';

			$result .= <<<HTML
              <!-- exhibitions body item -->
              <div class="exhibitions-body_item">
                <a href="/exhibition/{$id}/{$title}">
                <div class="exhibitions-body_item_img img-bg" data-img="/{$image}"></div>
                  <div class="exhibitions-content">
                    <p class="exhibitions-content_title">{$title}</p>
                    <div class="exhibitions_date">
                      <p class="exhibitions_date_time">{$dayFrom} {$monthFrom} - {$dayTo} {$monthTo}, {$year}</p>
                      <p class="exhibitions_date_text">{$type}</p>
                    </div>
                  </div>
                </a>
              </div>
              <!-- /exhibitions body item -->
HTML;
		}

		$result .= '</div></div>';

		$result .= $pagination;

		return $result;
	}

	public function getPinnedExhibitions ( $lang, $catId ) {
	    if(!isset($catId)) {
	        echo "<script>location.href='/'</script>";
	        exit;
        }

		$result = '';
		$sql = "SELECT `id`, `image1`, `title_{$lang}` `title`, `date_from`, `date_to`, DATE_FORMAT(`date_from`,'%d') `day_from`, DATE_FORMAT(`date_to`,'%d') `day_to`, DATE_FORMAT(`date_to`,'%Y') `year` FROM `exhibitions` WHERE `show_on_top` = '1' && `category` = '{$catId}' ORDER BY `date_from` DESC LIMIT 0,4";
		$query = $this->query ( $sql );
		while ( $q = $this->fetch ( $query ) ) {
			$id = $q [ 'id' ];
			$image = $q [ 'image1' ];
			$title = mb_substr ( $q [ 'title' ], 0, 60 ) . '...';

			$type = $this->getCurrentExhibitionType ( $lang, $id );

			$monthFrom = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_from' ] ) ) );
			$monthTo = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_to' ] ) ) );

			$dayFrom = $q [ 'day_from' ];
			$dayTo = $q [ 'day_to' ];
			$year = $q [ 'year' ];

			$result .= <<<HTML
              <div class="exhibitions-header_item item-is-6 img-bg" data-img="/{$image}">
              <p class="exhibitions-text">
                <a href="/exhibition/{$id}/{$title}" style="color: #ffffff;">{$title}</a>
              </p>
                <div class="exhibitions_date">
                  <p class="exhibitions_date_time">{$dayFrom} {$monthFrom} - {$dayTo} {$monthTo}, {$year}</p>
                  <p class="exhibitions_date_text is-red">{$type}</p>
                </div>
              </div>
HTML;
		}

		return $result;
	}

	public function getExhibitionTypes ( $lang ) {
		$result = [];
		$sql = "SELECT * FROM `exhibition_types`";
		$query = $this->query ( $sql );
		while ( $q = $this->fetch ( $query ) ) {
			$id = $q[ 'id' ];
			$title = $q [ 'title_' . $lang ];

			$result[ $id ] = $title;
		}

		return $result;
	}

	public function getCurrentExhibitionType ( $lang, $exhibitionId ) {
		$types = $this->getExhibitionTypes ( $lang );

		$sql = "SELECT * FROM `exhibitions` WHERE NOW() BETWEEN `date_from` AND `date_to` AND `id` = '{$exhibitionId}'";
		$query = $this->query ( $sql );

		if ( $this->numRows ( $query ) > 0 ) {
			return $types[ 2 ];
		}

		$sql = "SELECT * FROM `exhibitions` WHERE NOW()<`date_from` AND `id` = '{$exhibitionId}'";
		$query = $this->query ( $sql );

		if ( $this->numRows ( $query ) > 0 ) {
			return $types[ 1 ];
		}

		$sql = "SELECT * FROM `exhibitions` WHERE NOW()>`date_to` AND `id` = '{$exhibitionId}'";
		$query = $this->query ( $sql );

		if ( $this->numRows ( $query ) > 0 ) {
			return $types[ 3 ];
		}
	}

	public function getCurrentExhibitionSpan ( $lang, $exhibitionId ) {
		$types = $this->getExhibitionTypes ( $lang );

		$sql = "SELECT * FROM `exhibitions` WHERE NOW() BETWEEN `date_from` AND `date_to` AND `id` = '{$exhibitionId}'";
		$query = $this->query ( $sql );

		if ( $this->numRows ( $query ) > 0 ) {
			return $html = <<<HTML
                <span class="wrap-info_btn is-reading">{$types[2]}</span>
HTML;
		}

		$sql = "SELECT * FROM `exhibitions` WHERE NOW()<`date_from` AND `id` = '{$exhibitionId}'";
		$query = $this->query ( $sql );

		if ( $this->numRows ( $query ) > 0 ) {
			return $html = <<<HTML
                <span class="wrap-info_btn is-new">{$types[1]}</span>
HTML;
		}

		$sql = "SELECT * FROM `exhibitions` WHERE NOW()>`date_to` AND `id` = '{$exhibitionId}'";
		$query = $this->query ( $sql );

		if ( $this->numRows ( $query ) > 0 ) {
			return $html = <<<HTML
                <span class="wrap-info_btn is-read">{$types[3]}</span>
HTML;
		}
	}

	public function getExhibitionsMain ( $lang, $row ) {
		$limit = '';
		$result = '';

		if ( $row == 1 ) {
			$limit .= ' LIMIT 0, 3';
		} else if ( $row == 2 ) {
			$limit .= ' LIMIT 3, 3';
		} else {

			return FALSE;
		}

		$sql = "SELECT `id`, `title_{$lang}` `title`, `date_from`, `date_to`, DATE_FORMAT(`date_from`,'%d') `day_from`, DATE_FORMAT(`date_to`,'%d') `day_to`, DATE_FORMAT(`date_to`,'%Y') `year` FROM `exhibitions` ORDER BY `date_from` DESC{$limit}";
		$query = $this->query ( $sql );
		while ( $q = $this->fetch ( $query ) ) {
			$id = $q [ 'id' ];
			$type = $this->getCurrentExhibitionSpan ( $lang, $id );
			$monthFrom = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_from' ] ) ) );
			$monthTo = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_to' ] ) ) );

			$dayFrom = $q [ 'day_from' ];
			$dayTo = $q [ 'day_to' ];
			$year = $q [ 'year' ];

			$title = mb_substr ( $q [ 'title' ], 0, 60 ) . '...';


			$result .= <<<HTML
                    <div class="exh-links-wrapper_item">
                        <a href="/exhibition/{$id}/{$title}" class="links-wrapper_item_content">
                            {$title}
                        </a>
                        <div class="line"></div>
                        <p class="wrap_time">{$dayFrom} {$monthFrom} - {$dayTo} {$monthTo}, {$year}</p>
                        {$type}
                    </div>
HTML;
		}

		return $result;
	}

	public function getCollectionsMain ( $lang ) {
		$result = '';
		$sql = "SELECT cl.`name_{$lang}` `name`, cl.`image1` `image1`, cl.`year` `year`, cl.`author_{$lang}` `author`, cn.`name_{$lang}` `country` FROM `collections` cl LEFT JOIN `countries` cn ON (cl.country_id = cn.id)";
		$query = $this->query ( $sql );

		while ( $q = $this->fetch ( $query ) ) {
			$year = $q [ 'year' ];
			$image = $q [ 'image1' ];
			$author = $q [ 'author' ];
			$country = $q [ 'country' ];
			$title = mb_substr ( $q [ 'name' ], 0, 25 );

			$result .= <<<HMTL
          <!--<a href="">-->
            <div class="exhibition-slider_item img-bg" data-img="/{$image}" data-color="dark">
              <h2 class="exhibition-slider_item_title">{$title}</h2>
              <span class="year-date">{$year}</span>
              <div class="exhibition-slider_sub_item">
                <p>{$author}</p>
                <span>{$country}</span>
                <i class="fa fa-long-arrow-right" aria-hidden="true"></i>
              </div>
            </div>
          <!--</a>-->  
HMTL;
		}

		return $result;
	}

	public function getExhibitionInner ( $lang ) {
		if ( isset( $_GET[ 'exhibition_id' ] ) ) {
			$id = $this->secure_check_string ( $_GET[ 'exhibition_id' ] );
		} else {
			echo '<script>location.href="/exhibitions"</script>';
			exit;
		}

		$sql = "SELECT `id`, `image1`, `images1`, `title_{$lang}` `title`, `content_{$lang}` `content`, `date_from`, `date_to`, DATE_FORMAT(`date_from`,'%d') `day_from`, DATE_FORMAT(`date_to`,'%d') `day_to`, DATE_FORMAT(`date_to`,'%Y') `year` FROM `exhibitions` WHERE `id`={$id} ORDER BY `date_from` DESC";
		$query = $this->query ( $sql );

		if ( $this->numrows ( $query ) == 0 ) {
			echo '<script>location.href="/exhibitions"</script>';
			exit;
		}

		while ( $q = $this->fetch ( $query ) ) {
			$id = $q [ 'id' ];
			$image = $q [ 'image1' ];
			$type = $this->getCurrentExhibitionType ( $lang, $id );
			$monthFrom = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_from' ] ) ) );
			$monthTo = $this->getMonth ( date ( 'n', strtotime ( $q[ 'date_to' ] ) ) );
			$images = $q [ 'images1' ];
			$dayFrom = $q [ 'day_from' ];
			$dayTo = $q [ 'day_to' ];
			$year = $q [ 'year' ];
			$content = $q [ 'content' ];

			$title = mb_substr ( $q [ 'title' ], 0, 60 ) . '...';

			$innerExhibitionImages = explode ( ",", $images );

			$resultImage = '';

			foreach ( $innerExhibitionImages as $value ) {
				$resultImage .= <<<HTML
                <div class="grid-item grid-item-img">
                    <div class="images-block">
                      <a class="magnific-gallery" href="/{$value}"><img src="/{$value}"></a>
                    </div>
                </div>
HTML;

			}

			$result = [ 'title' => $title, 'type' => $type, 'content' => $content, 'image' => $image, 'images' => $resultImage, 'day_from' => $dayFrom, 'day_to' => $dayTo, 'month_from' => $monthFrom, 'month_to' => $monthTo, 'year' => $year ];

		}

		return $result;
	}

	public function generateHash ( $email ) {
		$sql = "SELECT hashcode FROM email_subscribers WHERE email='{$email}'";
		$query = $this->query ( $sql );
		if ( $this->numRows ( $query ) === 0 ) {
			return md5 ( time () . $email );
		} else {
			$q = $this->fetch ( $query );

			return $q[ 'hashcode' ];
		}
	}

	public function subscribeSendEmail ( $email ) {
		$hash = $this->generateHash ( $email );
		$hashLink = $this->domain2 . '/subscribe?hash=' . $hash;

		$subject = $this->langs[ 'subscribeEmailSubject' ];
		$message = $this->langs[ 'subscribeEmailMessage' ] . '<br />' . $hashLink;
		$this->sendEmail ( $email, $subject, $message );
	}

	public function subscribe ( $email ) {
		if ( !filter_var ( $email, FILTER_VALIDATE_EMAIL ) ) {
			return '0';
		}
		//Check if email exists in database
		$sql = "SELECT * FROM email_subscribers WHERE email='{$email}'";
		$query = $this->query ( $sql );
		if ( $this->numRows ( $query ) == 0 ) {
			$hash = $this->generateHash ( $email );
			$sql = "INSERT INTO email_subscribers (email, hashcode, confirmed) VALUES ('{$email}', '{$hash}', '0')";
			$this->query ( $sql );
			$this->subscribeSendEmail ( $email );

			return '1';
		} else {
			$q = $this->fetch ( $query );
			if ( $q[ 'confirmed' ] == '1' ) {
				return '2';
			} else {
				$this->subscribeSendEmail ( $email );

				return '3';
			}
		}
	}

	public function checkHash ( $hash ) {
		$hash = $this->secure_check_string ( $hash );
		$sql = "SELECT * FROM email_subscribers WHERE hashcode='{$hash}'";
		$query = $this->query ( $sql );
		if ( $this->numRows ( $query ) > 0 ) {
			$q = $this->fetch ( $query );
			$email = $q[ 'email' ];
			$sql = "UPDATE email_subscribers SET confirmed='1' WHERE email='{$email}'";
			$this->query ( $sql );

			return $this->langs[ 'subscribeActivated' ];
		} else {
			return $this->langs[ 'subscribeWrongHash' ];
		}
	}

	public function search () {
		if ( !isset( $_GET[ 'keyword' ] ) ) {
			return $this->langs[ 'noResults' ];
		}

		$keyword = $this->secure_check_string ( $_GET[ 'keyword' ] );
		$sql = <<<SQL
			SELECT id,
			   `type`,
			   title_az,
			   title_ru,
			   title_en,
			   content_az,
			   content_ru,
			   content_en
		FROM exhibitions
		WHERE title_az LIKE '%{$keyword}%'
		  OR title_ru LIKE '%{$keyword}%'
		  OR title_en LIKE '%{$keyword}%'
		  OR content_az LIKE '%{$keyword}%'
		  OR content_ru LIKE '%{$keyword}%'
		  OR content_en LIKE '%{$keyword}%'
		UNION ALL
		SELECT id,
			   `type`,
			   title_az,
			   title_ru,
			   title_en,
			   content_az,
			   content_ru,
			   content_en
		FROM news
		WHERE title_az LIKE '%{$keyword}%'
		  OR title_ru LIKE '%{$keyword}%'
		  OR title_en LIKE '%{$keyword}%'
		  OR content_az LIKE '%{$keyword}%'
		  OR content_ru LIKE '%{$keyword}%'
		  OR content_en LIKE '%{$keyword}%'
SQL;

		$per_page = $this->per_page;
		if ( isset( $_GET[ 'page' ] ) ) {
			$page = (int)$this->secure_check_string ( $_GET[ 'page' ] );
		} else {
			$page = 1;
		}

		$pagination = $this->generate_pagination ( $sql, $per_page, $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] );

		$limits = $this->generate_limit ( $page, $per_page );
		$sql = $sql . ' LIMIT ' . $limits;

		$query = $this->query ( $sql );

		if ( $this->numRows ( $query ) == 0 ) {
			return $this->langs[ 'noResults' ];
		}

		$result = '';
		while ( $q = $this->fetch ( $query ) ) {
			$id = $q[ 'id' ];
			$title = $q['title_'.$this->lang];
			if(strlen($title) > 100){
				$title = mb_substr ( $title, 0, 100 ) . '...';
			}

			$content = mb_substr ( $q [ 'content_' . $this->lang ], 0, 300 ) . '...';
			$type = $q[ 'type' ];

			$result .= <<<HTML
				<li class="search-results__item">
					<a href="/{$type}/{$id}/{$title}" class="search-results__link">{$title}</a>
					<p class="search-results__description">{$content}</p>
			    </li>
HTML;

		}

		$result .= '</ul>';

		$result .= $pagination;

		return $result;
	}

    public function getAllEmployees ( $lang ) {
        $sql = "SELECT * FROM `employees` ORDER BY `position`";

        $per_page = $this->per_page;
        if ( isset( $_GET[ 'page' ] ) ) {
            $page = (int)$this->secure_check_string ( $_GET[ 'page' ] );
        } else {
            $page = 1;
        }

        $pagination = $this->generate_pagination ( $sql, $per_page, $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] );

        $limits = $this->generate_limit ( $page, $per_page );
        $sql = $sql . ' LIMIT ' . $limits;

        $query = $this->query ( $sql );

        $result = '';

        while ( $q = $this->fetch ( $query ) ) {
            $id = $q[ 'id' ];
            $title = $q[ 'title_' . $lang ];
            $content = mb_substr ( $q[ 'content_' . $lang ], 0, 200 ) . "...";
            $image = $q[ 'image1' ];


            $result .= <<<HTML
           <div class="grid-item">
            <div class="news-block-item">
              <div class="news-block-image">
                <a href="/employee/{$id}/{$title}">
                  <img src="/{$image}" alt="image">
                </a>
              </div>
              <a href="/employee/{$id}/{$title}">
                <h5 class="blackLink news-title">{$title}</h5>
              </a>
              <p class="news-subtitle">{$content}</p>
            </div>
            </div>
HTML;

        }


        $result .= '</div></div>';

        $result .= $pagination;

        return $result;
    }

    public function getEmployeeInner ( $lang ) {
        if ( isset( $_GET[ 'employee_id' ] ) ) {
            $id = $this->secure_check_string ( $_GET[ 'employee_id' ] );
        } else {
            echo '<script>location.href="/all-news"</script>';
            exit;
        }

        $sql = "SELECT * FROM `employees` WHERE `id`={$id}";
        $query = $this->query ( $sql );

        if ( $this->numrows ( $query ) == 0 ) {
            echo '<script>location.href="/employees"</script>';
            exit;
        }

        $result = [];
        while ( $q = $this->fetch ( $query ) ) {
            $title = $q[ 'title_' . $lang ];
            $content = $q[ 'content_' . $lang ];
            $image = $q[ 'image1' ];
            $images = $q[ 'images1' ];

            $innerNewsImage = explode ( ",", $images );

            $resultImage = '';

            foreach ( $innerNewsImage as $value ) {
                $resultImage .= <<<HTML
                <div class="grid-item grid-item-img">
                    <div class="images-block">
                      <a class="magnific-gallery" href="/{$value}"><img src="/{$value}"></a>
                    </div>
                </div>       
HTML;

            }

            $result = [ 'title' => $title, 'content' => $content, 'image' => $image, 'images' => $resultImage ];

        }

        return $result;

    }

    public function getWhatsNewCategoryTitle($lang) {
	    if(isset($_GET['whatsnew_id'])){
	        $id = (int)$this->secure_check_string($_GET['whatsnew_id']);
        } else {
            echo "<script>location.href='/'</script>";
            exit;
        }

        $sql = "SELECT * FROM `whatsnew_categories` WHERE `id` = '{$id}'";
	    $query = $this->query($sql);
	    $q = $this->fetch($query);

	    return $q ['title_'.$lang];
    }

    public function getAllPublications ( $lang ) {
        $sql = "SELECT * FROM `publications` ORDER BY `position`";

        $per_page = $this->per_page;
        if ( isset( $_GET[ 'page' ] ) ) {
            $page = (int)$this->secure_check_string ( $_GET[ 'page' ] );
        } else {
            $page = 1;
        }

        $pagination = $this->generate_pagination ( $sql, $per_page, $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] );

        $limits = $this->generate_limit ( $page, $per_page );
        $sql = $sql . ' LIMIT ' . $limits;

        $query = $this->query ( $sql );

        $result = '';

        while ( $q = $this->fetch ( $query ) ) {
            $id = $q[ 'id' ];
            $title = $q[ 'title_' . $lang ];
            $content = mb_substr ( $q[ 'content_' . $lang ], 0, 200 ) . "...";
            $image = $q[ 'image1' ];


            $result .= <<<HTML
           <div class="grid-item">
            <div class="news-block-item">
              <div class="news-block-image">
                <a href="/publication/{$id}/{$title}">
                  <img src="/{$image}" alt="image">
                </a>
              </div>
              <a href="/employees/{$id}/{$title}">
                <h5 class="blackLink news-title">{$title}</h5>
              </a>
              <p class="news-subtitle">{$content}</p>
            </div>
            </div>
HTML;

        }


        $result .= '</div></div>';

        $result .= $pagination;

        return $result;
    }

    public function getPublicationInner ( $lang ) {
        if ( isset( $_GET[ 'publication_id' ] ) ) {
            $id = $this->secure_check_string ( $_GET[ 'publication_id' ] );
        } else {
            echo '<script>location.href="/publications"</script>';
            exit;
        }

        $sql = "SELECT * FROM `publications` WHERE `id`={$id}";
        $query = $this->query ( $sql );

        if ( $this->numrows ( $query ) == 0 ) {
            echo '<script>location.href="/publications"</script>';
            exit;
        }

        $result = [];
        while ( $q = $this->fetch ( $query ) ) {
            $title = $q[ 'title_' . $lang ];
            $content = $q[ 'content_' . $lang ];
            $image = $q[ 'image1' ];
            $images = $q[ 'images1' ];


            $innerNewsImage = explode ( ",", $images );

            $resultImage = '';

            foreach ( $innerNewsImage as $value ) {
                $resultImage .= <<<HTML
                <div class="grid-item grid-item-img">
                    <div class="images-block">
                      <a class="magnific-gallery" href="/{$value}"><img src="/{$value}"></a>
                    </div>
                </div>       
HTML;

            }

            $result = [ 'title' => $title, 'content' => $content, 'image' => $image, 'images' => $resultImage ];

        }

        return $result;

    }

    public function getSocialsFooter() {
        $icons = '';
	    $sql = "SELECT * FROM `social` WHERE `active` = '1' ORDER BY `position`";
	    $query = $this->query($sql);
	    while ($q = $this->fetch($query)) {
	        $icons .= <<<HTML
                <span class="socialLink"><a target="_blank" href="{$q ['link']}" class="blackLink" style="padding: 5px; border: 1px solid slategrey; border-radius: 3px; color: #333; font-size: 16px;"><i class="{$q ['icon_code']}" aria-hidden="true"></i></a></span>
HTML;

       }
       return $icons;
    }
}