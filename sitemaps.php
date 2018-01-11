<?php

#Path where Flarum is installed (NOT ends with slash)
$flarum_path = '/var/www/html/flarum';

#Abrir archivo de configuracion de Flarum
$config = include $flarum_path . '/config.php';

#Obtener las credenciales y datos de la base de datos mysql
$flarum_base_url = $config["url"];
$flarum_mysql_user = $config["database"]["username"];
$flarum_mysql_pass = $config["database"]["password"];
$flarum_mysql_host = $config["database"]["host"];
$flarum_mysql_db = $config["database"]["database"];
$flarum_mysql_db_prefix = $config["database"]["prefix"];


/*¿Cuantas urls puede haber como maximo en un sitemap?
* Google tiene unos límites a la hora de procesar los sitemaps y sólo procesará
* sitemaps que contengan 50000 URLs y de tamaño máximo (sin comprimir) de 10 MB
*/

// Creamos el indice de sitemaps
$sitemap_index_xml = BuildSitemapIndex($flarum_base_url);
$sitemap_index_path = $flarum_path . '/sitemap.xml';
WriteSitemapFile($sitemap_index_path, $sitemap_index_xml);

// Creamos el sitemap de las discusiones
$sitemap_posts_xml = BuildDiscussionsSitemap($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db, $flarum_mysql_db_prefix, $flarum_base_url);
$sitemap_posts_path = $flarum_path . '/sitemap-posts.xml';
WriteSitemapFile($sitemap_posts_path, $sitemap_posts_xml);

// Creamos el sitemap de las etiquetas
$sitemap_tags_xml = BuildTagsSitemap($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db, $flarum_mysql_db_prefix, $flarum_base_url);
$sitemap_tags_path = $flarum_path . '/sitemap-tags.xml';
WriteSitemapFile($sitemap_tags_path, $sitemap_tags_xml);

// Creamos el sitemap de los usuarios
$sitemap_users_xml = BuildUsersSitemap($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db, $flarum_mysql_db_prefix, $flarum_base_url);
$sitemap_users_path = $flarum_path . '/sitemap-users.xml';
WriteSitemapFile($sitemap_users_path, $sitemap_users_xml);

/**
* Function to generate XML code of the sitemap index
* @param string $flarum_base_url Base forum URL
* @return string The sitemap index XML contents
*/
function BuildSitemapIndex($flarum_base_url)
{
  $sitemaps = array('sitemap-posts.xml', 'sitemap-users.xml', 'sitemap-tags.xml');

  //Open Sitemap XML Header
  $sitemap_contents = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  $sitemap_contents .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  //Add different sitemaps files to sitemapindex
  foreach ($sitemaps as $res) {
    $sitemap_contents .= "\n\t" . '<sitemap>';
    $sitemap_contents .= "\n\t\t" . '<loc>' . EscapeXML($flarum_base_url) . '/' . $res . '</loc>';
    $sitemap_contents .= "\n\t\t" . '<lastmod>' . date('Y-m-d\TH:i:s+00:00') . '</lastmod>';
    $sitemap_contents .= "\n\t" . '</sitemap>';
  }

  //Close Sitemap XML Header
  $sitemap_contents .= "\n" . '</sitemapindex>';

  return $sitemap_contents;
}

/**
* Function to generate XML code of the discussions sitemap
* @param string $flarum_mysql_host Base forum URL
* @param string $flarum_mysql_user Base forum URL
* @param string $flarum_mysql_pass Base forum URL
* @param string $flarum_mysql_db Base forum URL
* @param string $flarum_mysql_db_prefix Base forum URL
* @param string $flarum_base_url Base forum URL
* @return string The discussions sitemap XML contents
*/
function BuildDiscussionsSitemap($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db, $flarum_mysql_db_prefix, $flarum_base_url)
{
  // Create MySQL connection
  $conn = new mysqli($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db);
  mysqli_set_charset($conn, "utf8");

  // Check connection
  if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
  }

  // Run SQL query
  $sql = "SELECT id, slug, last_time FROM " . $flarum_mysql_db_prefix . "discussions WHERE is_approved=1 AND is_private=0";
  $result = mysqli_query($conn, $sql);

  // Build XML data with MySQL data
  $sitemap_contents = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  $sitemap_contents .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  if (mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
        $sitemap_contents .= "\n\t<url>\n";
        $sitemap_contents .= "\t\t<loc>" . EscapeXML($flarum_base_url) . '/d/' . $row["id"] . '-' . EscapeXML($row["slug"]) . "</loc>\n";
        $sitemap_contents .= "\t\t<lastmod>" . date('Y-m-d\TH:i:s+00:00' , strtotime($row["last_time"])) . "</lastmod>\n";
        $sitemap_contents .= "\t\t<changefreq>" . 'weekly' . "</changefreq>\n";
        $sitemap_contents .= "\t\t<priority>" . '0.8' . "</priority>\n";
        $sitemap_contents .= "\t</url>";
      }
  } else {
      echo "0 results";
  }

  //Close Sitemap XML Header
  $sitemap_contents .= "\n" . '</urlset>';

  // Close MySQL connection
  $conn->close();

  return $sitemap_contents;
}

/**
* Function to generate XML code of the tags sitemap
* @param string $flarum_mysql_host Base forum URL
* @param string $flarum_mysql_user Base forum URL
* @param string $flarum_mysql_pass Base forum URL
* @param string $flarum_mysql_db Base forum URL
* @param string $flarum_mysql_db_prefix Base forum URL
* @param string $flarum_base_url Base forum URL
* @return string The tags sitemap XML contents
*/
function BuildTagsSitemap($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db, $flarum_mysql_db_prefix, $flarum_base_url)
{
  // Create MySQL connection
  $conn = new mysqli($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db);
  mysqli_set_charset($conn, "utf8");

  // Check connection
  if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
  }

  // Run SQL query
  $sql = "SELECT slug, last_time FROM " . $flarum_mysql_db_prefix . "tags WHERE is_restricted=0 AND is_hidden=0";
  $result = mysqli_query($conn, $sql);

  // Build XML data with MySQL data
  $sitemap_contents = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  $sitemap_contents .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  if (mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
        $sitemap_contents .= "\n\t<url>\n";
        $sitemap_contents .= "\t\t<loc>" . EscapeXML($flarum_base_url) . '/t/' . EscapeXML($row["slug"]) . "</loc>\n";
        $sitemap_contents .= "\t\t<lastmod>" . date('Y-m-d\TH:i:s+00:00' , strtotime($row["last_time"])) . "</lastmod>\n";
        $sitemap_contents .= "\t\t<changefreq>" . 'daily' . "</changefreq>\n";
        $sitemap_contents .= "\t\t<priority>" . '0.5' . "</priority>\n";
        $sitemap_contents .= "\t</url>";
      }
  } else {
      echo "0 results";
  }

  //Close Sitemap XML Header
  $sitemap_contents .= "\n" . '</urlset>';

  // Close MySQL connection
  $conn->close();

  return $sitemap_contents;
}

/**
* Function to generate XML code of the users sitemap
* @param string $flarum_mysql_host Base forum URL
* @param string $flarum_mysql_user Base forum URL
* @param string $flarum_mysql_pass Base forum URL
* @param string $flarum_mysql_db Base forum URL
* @param string $flarum_mysql_db_prefix Base forum URL
* @param string $flarum_base_url Base forum URL
* @return string The tags sitemap XML contents
*/
function BuildUsersSitemap($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db, $flarum_mysql_db_prefix, $flarum_base_url)
{
  // Create MySQL connection
  $conn = new mysqli($flarum_mysql_host, $flarum_mysql_user, $flarum_mysql_pass, $flarum_mysql_db);
  mysqli_set_charset($conn, "utf8");

  // Check connection
  if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
  }

  // Run SQL query
  $sql = "SELECT username, last_seen_time FROM " . $flarum_mysql_db_prefix . "users WHERE is_activated=1";
  $result = mysqli_query($conn, $sql);

  // Build XML data with MySQL data
  $sitemap_contents = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  $sitemap_contents .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  if (mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
        $sitemap_contents .= "\n\t<url>\n";
        $sitemap_contents .= "\t\t<loc>" . EscapeXML($flarum_base_url) . '/u/' . EscapeXML($row["username"]) . "</loc>\n";
        $sitemap_contents .= "\t\t<lastmod>" . date('Y-m-d\TH:i:s+00:00' , strtotime($row["last_seen_time"])) . "</lastmod>\n";
        $sitemap_contents .= "\t\t<changefreq>" . 'daily' . "</changefreq>\n";
        $sitemap_contents .= "\t\t<priority>" . '0.4' . "</priority>\n";
        $sitemap_contents .= "\t</url>";
      }
  } else {
      echo "0 results";
  }

  //Close Sitemap XML Header
  $sitemap_contents .= "\n" . '</urlset>';

  // Close MySQL connection
  $conn->close();

  return $sitemap_contents;
}

/**
* Function to write sitemap files to disk
* @param string $path Path where sitemap will be stored
* @param string $contents XML contents of the sitemap
*/
function WriteSitemapFile($path, $contents)
{
  $fh = fopen($path, 'w') or die("Cant create file");
  fwrite($fh, $contents);
  fclose($fh);
}

/**
* Function to sanitize special chars to use in sitemaps
* @param string $string Input string to sanitize
* @return string Sanitized string
*/
function EscapeXML($string)
{
  return str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $string);
}

?>
