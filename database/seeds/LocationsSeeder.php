<?php
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    /**
     * List of countries.
     */
    private $countries = ['AF' => "Afghanistan", 'AX' => "Åland Islands", 'AL' => "Albania", 'DZ' => "Algeria", 'AS' => "American Samoa", 'AD' => "Andorra", 'AO' => "Angola", 'AI' => "Anguilla", 'AQ' => "Antarctica", 'AG' => "Antigua and Barbuda", 'AR' => "Argentina", 'AM' => "Armenia", 'AW' => "Aruba", 'AU' => "Australia", 'AT' => "Austria", 'AZ' => "Azerbaijan", 'BS' => "Bahamas", 'BH' => "Bahrain", 'BD' => "Bangladesh", 'BB' => "Barbados", 'BY' => "Belarus", 'BE' => "Belgium", 'BZ' => "Belize", 'BJ' => "Benin", 'BM' => "Bermuda", 'BT' => "Bhutan", 'BO' => "Bolivia, Plurinational State of", 'BQ' => "Bonaire, Sint Eustatius and Saba", 'BA' => "Bosnia and Herzegovina", 'BW' => "Botswana", 'BV' => "Bouvet Island", 'BR' => "Brazil", 'IO' => "British Indian Ocean Territory", 'BN' => "Brunei Darussalam", 'BG' => "Bulgaria", 'BF' => "Burkina Faso", 'BI' => "Burundi", 'KH' => "Cambodia", 'CM' => "Cameroon", 'CA' => "Canada", 'CV' => "Cape Verde", 'KY' => "Cayman Islands", 'CF' => "Central African Republic", 'TD' => "Chad", 'CL' => "Chile", 'CN' => "China", 'CX' => "Christmas Island", 'CC' => "Cocos (Keeling) Islands", 'CO' => "Colombia", 'KM' => "Comoros", 'CG' => "Congo", 'CD' => "Congo, the Democratic Republic of the", 'CK' => "Cook Islands", 'CR' => "Costa Rica", 'CI' => "Côte d''Ivoire", 'HR' => "Croatia", 'CU' => "Cuba", 'CW' => "Curaçao", 'CY' => "Cyprus", 'CZ' => "Czech Republic", 'DK' => "Denmark", 'DJ' => "Djibouti", 'DM' => "Dominica", 'DO' => "Dominican Republic", 'EC' => "Ecuador", 'EG' => "Egypt", 'SV' => "El Salvador", 'GQ' => "Equatorial Guinea", 'ER' => "Eritrea", 'EE' => "Estonia", 'ET' => "Ethiopia", 'FK' => "Falkland Islands (Malvinas)", 'FO' => "Faroe Islands", 'FJ' => "Fiji", 'FI' => "Finland", 'FR' => "France", 'GF' => "French Guiana", 'PF' => "French Polynesia", 'TF' => "French Southern Territories", 'GA' => "Gabon", 'GM' => "Gambia", 'GE' => "Georgia", 'DE' => "Germany", 'GH' => "Ghana", 'GI' => "Gibraltar", 'GR' => "Greece", 'GL' => "Greenland", 'GD' => "Grenada", 'GP' => "Guadeloupe", 'GU' => "Guam", 'GT' => "Guatemala", 'GG' => "Guernsey", 'GN' => "Guinea", 'GW' => "Guinea-Bissau", 'GY' => "Guyana", 'HT' => "Haiti", 'HM' => "Heard Island and McDonald Islands", 'VA' => "Holy See (Vatican City State", 'HN' => "Honduras", 'HK' => "Hong Kong", 'HU' => "Hungary", 'IS' => "Iceland", 'IN' => "India", 'ID' => "Indonesia", 'IR' => "Iran, Islamic Republic of", 'IQ' => "Iraq", 'IE' => "Ireland", 'IM' => "Isle of Man", 'IL' => "Israel", 'IT' => "Italy", 'JM' => "Jamaica", 'JP' => "Japan", 'JE' => "Jersey", 'JO' => "Jordan", 'KZ' => "Kazakhstan", 'KE' => "Kenya", 'KI' => "Kiribati", 'KP' => "Korea, Democratic People''s Republic of", 'KR' => "Korea, Republic of", 'KW' => "Kuwait", 'KG' => "Kyrgyzstan", 'LA' => "Lao People''s Democratic Republic", 'LV' => "Latvia", 'LB' => "Lebanon", 'LS' => "Lesotho", 'LR' => "Liberia", 'LY' => "Libyan Arab Jamahiriya", 'LI' => "Liechtenstein", 'LT' => "Lithuania", 'LU' => "Luxembourg", 'MO' => "Macao", 'MK' => "Macedonia, the former Yugoslav Republic of", 'MG' => "Madagascar", 'MW' => "Malawi", 'MY' => "Malaysia", 'MV' => "Maldives", 'ML' => "Mali", 'MT' => "Malta", 'MH' => "Marshall Islands", 'MQ' => "Martinique", 'MR' => "Mauritania", 'MU' => "Mauritius", 'YT' => "Mayotte", 'MX' => "Mexico", 'FM' => "Micronesia, Federated States of", 'MD' => "Moldova, Republic of", 'MC' => "Monaco", 'MN' => "Mongolia", 'ME' => "Montenegro", 'MS' => "Montserrat", 'MA' => "Morocco", 'MZ' => "Mozambique", 'MM' => "Myanmar", 'NA' => "Namibia", 'NR' => "Nauru", 'NP' => "Nepal", 'NL' => "Netherlands", 'NC' => "New Caledonia", 'NZ' => "New Zealand", 'NI' => "Nicaragua", 'NE' => "Niger", 'NG' => "Nigeria", 'NU' => "Niue", 'NF' => "Norfolk Island", 'MP' => "Northern Mariana Islands", 'NO' => "Norway", 'OM' => "Oman", 'PK' => "Pakistan", 'PW' => "Palau", 'PS' => "Palestinian Territory, Occupied", 'PA' => "Panama", 'PG' => "Papua New Guinea", 'PY' => "Paraguay", 'PE' => "Peru", 'PH' => "Philippines", 'PN' => "Pitcairn", 'PL' => "Poland", 'PT' => "Portugal", 'PR' => "Puerto Rico", 'QA' => "Qatar", 'RE' => "Réunion", 'RO' => "Romania", 'RU' => "Russian Federation", 'RW' => "Rwanda", 'BL' => "Saint Barthélemy", 'SH' => "Saint Helena, Ascension and Tristan da Cunha", 'KN' => "Saint Kitts and Nevis", 'LC' => "Saint Lucia", 'MF' => "Saint Martin (French part", 'PM' => "Saint Pierre and Miquelon", 'VC' => "Saint Vincent and the Grenadines", 'WS' => "Samoa", 'SM' => "San Marino", 'ST' => "Sao Tome and Principe", 'SA' => "Saudi Arabia", 'SN' => "Senegal", 'RS' => "Serbia", 'SC' => "Seychelles", 'SL' => "Sierra Leone", 'SG' => "Singapore", 'SX' => "Sint Maarten (Dutch part", 'SK' => "Slovakia", 'SI' => "Slovenia", 'SB' => "Solomon Islands", 'SO' => "Somalia", 'ZA' => "South Africa", 'GS' => "South Georgia and the South Sandwich Islands", 'SS' => "South Sudan", 'ES' => "Spain", 'LK' => "Sri Lanka", 'SD' => "Sudan", 'SR' => "Suriname", 'SJ' => "Svalbard and Jan Mayen", 'SZ' => "Swaziland", 'SE' => "Sweden", 'CH' => "Switzerland", 'SY' => "Syrian Arab Republic", 'TW' => "Taiwan, Province of China", 'TJ' => "Tajikistan", 'TZ' => "Tanzania, United Republic of", 'TH' => "Thailand", 'TL' => "Timor-Leste", 'TG' => "Togo", 'TK' => "Tokelau", 'TO' => "Tonga", 'TT' => "Trinidad and Tobago", 'TN' => "Tunisia", 'TR' => "Turkey", 'TM' => "Turkmenistan", 'TC' => "Turks and Caicos Islands", 'TV' => "Tuvalu", 'UG' => "Uganda", 'UA' => "Ukraine", 'AE' => "United Arab Emirates", 'GB' => "United Kingdom", 'US' => "United States", 'UM' => "United States Minor Outlying Islands", 'UY' => "Uruguay", 'UZ' => "Uzbekistan", 'VU' => "Vanuatu", 'VE' => "Venezuela, Bolivarian Republic of", 'VN' => "Viet Nam", 'VG' => "Virgin Islands, British", 'VI' => "Virgin Islands, U.S", 'WF' => "Wallis and Futuna", 'EH' => "Western Sahara", 'YE' => "Yemen", 'ZM' => "Zambia", 'ZW' => "Zimbabwe"];

    /**
     * Execute the console command.
     * @return void
     */
    public function run()
    {
        try {
            // Start the transaction.
            // DB::beginTransaction();

            // Reset the tables.
            DB::statement('TRUNCATE TABLE core.locations, core.communities, core.cities, core.states, core.countries RESTART IDENTITY CASCADE');

            // Insert the countries.
            foreach ($this->countries as $code => $name) {
                DB::table('core.countries')->insert(['code' => $code, 'name' => $name, 'created_at' => 'now()']);
            }

            // Fetch the country code - ID mapping.
            $countries = DB::table('core.countries')->orderBy('code', 'asc')->pluck('id', 'code');

            // Open the postal code dump provided by Geonames.
            // http://download.geonames.org/export/zip/
            $path = dirname(__FILE__) . '/../data/geonames/postal_codes.txt';
            $handle = fopen($path, 'r');

            if ($handle === false) {
                throw new Exception('Unable to open file: ' . $path);
            }

            // Process the CSV file.
            while (($data = fgetcsv($handle, 0, "\t")) !== false) {
                // Fetch the columns.
                $country_code = $data[0];
                $postal_code = $data[1];
                $place = mb_convert_case($data[2], MB_CASE_TITLE);
                $state = mb_convert_case($data[3], MB_CASE_TITLE);
                $state_code = $data[4];
                $city = mb_convert_case($data[5], MB_CASE_TITLE);
                $city_code = $data[6];
                $community = mb_convert_case($data[7], MB_CASE_TITLE);
                $community_code = $data[8];
                $latitude = ($data[9]) ?: null;
                $longitude = ($data[10]) ?: null;

                // Set the country ID.
                $country_id = $countries[$country_code];

                // Create the state record.
                if ($state) {
                    // Check if the state exists.
                    $state_id = DB::table('core.states')->where([['country_id', $country_id], ['name', $state]])->value('id');

                    if (!$state_id) {
                        // Create the state and get the ID.
                        $state_id = DB::table('core.states')->insertGetId(['country_id' => $country_id, 'code' => $state_code, 'name' => $state, 'created_at' => 'now()']);
                    }

                    // Create the city.
                    if ($city) {
                        // Check if the city exists.
                        $city_id = DB::table('core.cities')->where([['country_id', $country_id], ['state_id', $state_id], ['name', $city]])->value('id');

                        if (!$city_id) {
                            // Create the city and get the ID.
                            $city_id = DB::table('core.cities')->insertGetId(['country_id' => $country_id, 'state_id' => $state_id, 'code' => $city_code, 'name' => $city, 'created_at' => 'now()']);
                        }

                        // Create the community.
                        if ($community) {
                            // Check if the community exists.
                            $community_id = DB::table('core.communities')->where([['city_id', $city_id], ['name', $community]])->value('id');

                            if (!$community_id) {
                                // Create the community and get the ID.
                                $community_id = DB::table('core.communities')->insertGetId(['city_id' => $city_id, 'code' => $community_code, 'name' => $community, 'created_at' => 'now()']);
                            }
                        } else {
                            // Community is not provided.
                            $community_id = null;
                        }
                    } else {
                        // City is not provided.
                        $city_id = null;
                        $community_id = null;
                    }
                } else {
                    // State is not provided.
                    $state_id = null;
                    $city_id = null;
                    $community_id = null;
                }

                // Create the location.
                try {
                    $params = ['country_id' => $country_id, 'state_id' => $state_id, 'city_id' => $city_id, 'community_id' => $community_id, 'postal_code' => $postal_code, 'latitude' => $latitude, 'longitude' => $longitude, 'name' => $place, 'created_at' => 'now()'];
                    $location_id = DB::table('core.locations')->insertGetId($params);
                    echo json_encode($params) . "\n";
                } catch (Exception $e) {
                    if ($e->getCode() == 23505) {
                        // The location exists, continue.
                        echo 'Warning: duplicate location - ' . json_encode($params) . "\n";
                    } else {
                        throw $e;
                    }
                }
            }

            // Close the resource.
            fclose($handle);

            // Migrate the rest of the cities.
            // Open the cities dump provided by Geonames.
            // Note: cities.txt is a combination of cities1000.txt, cities5000.txt, and cities15000.txt.
            // http://download.geonames.org/export/dump/
            $path = dirname(__FILE__) . '/../data/geonames/cities.txt';
            $handle = fopen($path, 'r');

            if ($handle === false) {
                throw new Exception('Unable to open file: ' . $path);
            }

            // Process the CSV file.
            while (($data = fgetcsv($handle, 0, "\t")) !== false) {
                // Fetch the columns.
                $geoname_id = $data[0];
                $city = mb_convert_case($data[1], MB_CASE_TITLE);
                $city_ascii = $data[2];
                $city_alt = $data[3];
                $latitude = ($data[4]) ?: null;
                $longitude = ($data[5]) ?: null;
                $feature_class = $data[6];
                $feature_code = $data[7];
                $country_code = $data[8];
                $country_code_2 = $data[9];
                $state_code = $data[10];
                $timezone = $data[17];

                // Set the country ID.
                $country_id = isset($countries[$country_code]) ? $countries[$country_code] : null;

                if ($country_id) {
                    // Check if the state exists.
                    $state_id = DB::table('core.states')->where([['country_id', $country_id], ['code', $state_code]])->value('id');
                    $state_id = ($state_id) ? $state_id : null;

                    // Check if the city exists.
                    $city_id = DB::table('core.cities')->where([['country_id', $country_id], ['state_id', $state_id], ['name', $city]])->value('id');

                    if ($city_id) {
                        // Update the city.
                        DB::table('core.cities')->where('id', $city_id)->update(['timezone' => $timezone, 'latitude' => $latitude, 'longitude' => $longitude]);
                    } else {
                        // Create the city and get the ID.
                        $params = ['country_id' => $country_id, 'state_id' => $state_id, 'name' => $city, 'timezone' => $timezone, 'latitude' => $latitude, 'longitude' => $longitude, 'created_at' => 'now()'];
                        $city_id = DB::table('core.cities')->insertGetId($params);
                        echo json_encode($params) . "\n";
                    }
                }
            }

            // Commit.
            // DB::commit();
        } catch (Exception $e) {
            // Rollback.
            // DB::rollBack();

            // Display the error.
            echo $e->getCode() . ': ' . $e->getMessage() . "\n";
        }
    }
}
