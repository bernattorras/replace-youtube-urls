# Search Replace URLs Plugin

This WordPress plugin allows you to search for instances of 'moneymaven.io' in the database and perform various operations on the matching URLs.

## Installation

1. Clone this repository to your WordPress plugins directory.
   ```bash
   git clone https://github.com/your-username/search-replace-urls.git

2. Activate the plugin through the WordPress admin interface.

## Usage

### Command Line Interface (CLI)

The plugin provides a set of CLI commands to search and manipulate URLs in the database.

- **wp search-replace-urls search --search-string**
  - Specify the search string to use.

- **wp search-replace-urls search --reduce-urls**
  - If provided, remove the last 2 segments from each URL in `urls_found.csv` and save to `urls_found_reduced.csv`.

- **wp search-replace-urls search --find-similar-urls**
  - If provided, find similar post names in the `wp_posts` table for each "reduced segment" in `urls_found.csv` and update the CSV.

- **wp search-replace-urls search --replace-urls**
  - If provided, replace the original URLs in `urls_found_similar.csv` with the permalinks from the `wp_posts` table.

## Examples

- **wp search-replace-urls search --search-string=THE_WANTED_URL**
  - Search for a specific URL.

- **wp search-replace-urls search --reduce-urls**
  - Reduce URLs in `urls_found.csv`.

- **wp search-replace-urls search --find-similar-urls**
  - Find similar URLs in the `wp_posts` table for each URL in `urls_found_reduced.csv`.

- **wp search-replace-urls search --replace-urls**
  - Replace original URLs in `urls_found_similar.csv` with permalinks from the `wp_posts` table.
