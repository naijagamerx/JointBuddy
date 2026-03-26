<?php
/**
 * Location Data Service
 *
 * Provides country, province/state, and city data for checkout forms.
 * Supports dependent dropdowns for address selection.
 *
 * @package CannaBuddy
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

/**
 * Get all available countries
 *
 * @return array List of countries with codes
 */
function getLocationCountries(): array {
    return [
        ['code' => 'ZA', 'name' => 'South Africa'],
        ['code' => 'NG', 'name' => 'Nigeria'],
        ['code' => 'US', 'name' => 'United States'],
        ['code' => 'GB', 'name' => 'United Kingdom'],
    ];
}

/**
 * Get provinces/states for a country
 *
 * @param string $countryCode Country code (ZA, NG, US, GB)
 * @return array List of provinces/states
 */
function getLocationProvinces(string $countryCode): array {
    $provinces = [
        'ZA' => [ // South Africa - 9 Provinces
            ['code' => 'EC', 'name' => 'Eastern Cape'],
            ['code' => 'FS', 'name' => 'Free State'],
            ['code' => 'GP', 'name' => 'Gauteng'],
            ['code' => 'KZN', 'name' => 'KwaZulu-Natal'],
            ['code' => 'LP', 'name' => 'Limpopo'],
            ['code' => 'MP', 'name' => 'Mpumalanga'],
            ['code' => 'NC', 'name' => 'Northern Cape'],
            ['code' => 'NW', 'name' => 'North West'],
            ['code' => 'WC', 'name' => 'Western Cape'],
        ],
        'NG' => [ // Nigeria - 36 States + FCT
            ['code' => 'AB', 'name' => 'Abia'],
            ['code' => 'AD', 'name' => 'Adamawa'],
            ['code' => 'AK', 'name' => 'Akwa Ibom'],
            ['code' => 'AN', 'name' => 'Anambra'],
            ['code' => 'BA', 'name' => 'Bauchi'],
            ['code' => 'BY', 'name' => 'Bayelsa'],
            ['code' => 'BE', 'name' => 'Benue'],
            ['code' => 'BO', 'name' => 'Borno'],
            ['code' => 'CR', 'name' => 'Cross River'],
            ['code' => 'DE', 'name' => 'Delta'],
            ['code' => 'EB', 'name' => 'Ebonyi'],
            ['code' => 'ED', 'name' => 'Edo'],
            ['code' => 'EK', 'name' => 'Ekiti'],
            ['code' => 'EN', 'name' => 'Enugu'],
            ['code' => 'FC', 'name' => 'Federal Capital Territory'],
            ['code' => 'GO', 'name' => 'Gombe'],
            ['code' => 'IM', 'name' => 'Imo'],
            ['code' => 'JI', 'name' => 'Jigawa'],
            ['code' => 'KD', 'name' => 'Kaduna'],
            ['code' => 'KN', 'name' => 'Kano'],
            ['code' => 'KT', 'name' => 'Katsina'],
            ['code' => 'KE', 'name' => 'Kebbi'],
            ['code' => 'KO', 'name' => 'Kogi'],
            ['code' => 'KW', 'name' => 'Kwara'],
            ['code' => 'LA', 'name' => 'Lagos'],
            ['code' => 'NA', 'name' => 'Nasarawa'],
            ['code' => 'NI', 'name' => 'Niger'],
            ['code' => 'OG', 'name' => 'Ogun'],
            ['code' => 'ON', 'name' => 'Ondo'],
            ['code' => 'OS', 'name' => 'Osun'],
            ['code' => 'OY', 'name' => 'Oyo'],
            ['code' => 'PL', 'name' => 'Plateau'],
            ['code' => 'RI', 'name' => 'Rivers'],
            ['code' => 'SO', 'name' => 'Sokoto'],
            ['code' => 'TA', 'name' => 'Taraba'],
            ['code' => 'YO', 'name' => 'Yobe'],
            ['code' => 'ZA', 'name' => 'Zamfara'],
        ],
        'US' => [ // United States - 50 States
            ['code' => 'AL', 'name' => 'Alabama'],
            ['code' => 'AK', 'name' => 'Alaska'],
            ['code' => 'AZ', 'name' => 'Arizona'],
            ['code' => 'AR', 'name' => 'Arkansas'],
            ['code' => 'CA', 'name' => 'California'],
            ['code' => 'CO', 'name' => 'Colorado'],
            ['code' => 'CT', 'name' => 'Connecticut'],
            ['code' => 'DE', 'name' => 'Delaware'],
            ['code' => 'FL', 'name' => 'Florida'],
            ['code' => 'GA', 'name' => 'Georgia'],
            ['code' => 'HI', 'name' => 'Hawaii'],
            ['code' => 'ID', 'name' => 'Idaho'],
            ['code' => 'IL', 'name' => 'Illinois'],
            ['code' => 'IN', 'name' => 'Indiana'],
            ['code' => 'IA', 'name' => 'Iowa'],
            ['code' => 'KS', 'name' => 'Kansas'],
            ['code' => 'KY', 'name' => 'Kentucky'],
            ['code' => 'LA', 'name' => 'Louisiana'],
            ['code' => 'ME', 'name' => 'Maine'],
            ['code' => 'MD', 'name' => 'Maryland'],
            ['code' => 'MA', 'name' => 'Massachusetts'],
            ['code' => 'MI', 'name' => 'Michigan'],
            ['code' => 'MN', 'name' => 'Minnesota'],
            ['code' => 'MS', 'name' => 'Mississippi'],
            ['code' => 'MO', 'name' => 'Missouri'],
            ['code' => 'MT', 'name' => 'Montana'],
            ['code' => 'NE', 'name' => 'Nebraska'],
            ['code' => 'NV', 'name' => 'Nevada'],
            ['code' => 'NH', 'name' => 'New Hampshire'],
            ['code' => 'NJ', 'name' => 'New Jersey'],
            ['code' => 'NM', 'name' => 'New Mexico'],
            ['code' => 'NY', 'name' => 'New York'],
            ['code' => 'NC', 'name' => 'North Carolina'],
            ['code' => 'ND', 'name' => 'North Dakota'],
            ['code' => 'OH', 'name' => 'Ohio'],
            ['code' => 'OK', 'name' => 'Oklahoma'],
            ['code' => 'OR', 'name' => 'Oregon'],
            ['code' => 'PA', 'name' => 'Pennsylvania'],
            ['code' => 'RI', 'name' => 'Rhode Island'],
            ['code' => 'SC', 'name' => 'South Carolina'],
            ['code' => 'SD', 'name' => 'South Dakota'],
            ['code' => 'TN', 'name' => 'Tennessee'],
            ['code' => 'TX', 'name' => 'Texas'],
            ['code' => 'UT', 'name' => 'Utah'],
            ['code' => 'VT', 'name' => 'Vermont'],
            ['code' => 'VA', 'name' => 'Virginia'],
            ['code' => 'WA', 'name' => 'Washington'],
            ['code' => 'WV', 'name' => 'West Virginia'],
            ['code' => 'WI', 'name' => 'Wisconsin'],
            ['code' => 'WY', 'name' => 'Wyoming'],
        ],
        'GB' => [ // United Kingdom - 4 Nations
            ['code' => 'ENG', 'name' => 'England'],
            ['code' => 'SCT', 'name' => 'Scotland'],
            ['code' => 'WLS', 'name' => 'Wales'],
            ['code' => 'NIR', 'name' => 'Northern Ireland'],
        ],
    ];

    return $provinces[$countryCode] ?? [];
}

/**
 * Get cities for a province/state
 *
 * @param string $countryCode Country code
 * @param string $provinceCode Province/state code
 * @return array List of cities
 */
function getLocationCities(string $countryCode, string $provinceCode): array {
    $cities = [
        // South Africa Cities
        'ZA' => [
            'EC' => ['Port Elizabeth', 'East London', 'Gqeberha', 'Mthatha'],
            'FS' => ['Bloemfontein', 'Welkom', 'Bethlehem'],
            'GP' => ['Johannesburg', 'Pretoria', 'Soweto', 'Sandton', 'Temba', 'Soshanguve'],
            'KZN' => ['Durban', 'Pietermaritzburg', 'Umhlanga', 'Ballito'],
            'LP' => ['Polokwane', 'Mokopane', 'Lebowakgomo'],
            'MP' => ['Nelspruit', 'Witbank', 'Secunda', 'Mbombela'],
            'NC' => ['Kimberley', 'Upington', 'Kuruman'],
            'NW' => ['Rustenburg', 'Klerksdorp', 'Mahikeng'],
            'WC' => ['Cape Town', 'Stellenbosch', 'George', 'Paarl'],
        ],
        // Nigeria Cities
        'NG' => [
            'LA' => ['Ikeja', 'Lekki', 'Victoria Island', 'Surulere', 'Ikeja', 'Ajah'],
            'FC' => ['Wuse', 'Maitama', 'Gwarinpa', 'Abuja', 'Gwagwalada'],
            'KN' => ['Kano', 'Sabon Gari', 'Wudil'],
            'RI' => ['Port Harcourt', 'Obio-Akpor'],
            'OG' => ['Abeokuta', 'Ijebu Ode', 'Sagamu'],
            'OY' => ['Ibadan', 'Oyo', 'Ogbomoso'],
            'ED' => ['Benin City', 'Ekpoma'],
            'DE' => ['Warri', 'Asaba', 'Ughelli'],
            'AN' => ['Awka', 'Onitsha', 'Nnewi'],
            'IM' => ['Owerri', 'Orlu'],
            'EN' => ['Enugu', 'Nsukka'],
            'AK' => ['Uyo', 'Eket'],
            'CR' => ['Calabar', 'Ikom'],
            'KD' => ['Kaduna', 'Zaria', 'Kafanchan'],
            'PL' => ['Jos', 'Bukuru'],
            'BA' => ['Bauchi', 'Azare'],
            'YO' => ['Damaturu', 'Gashua'],
            'BO' => ['Maiduguri', 'Bama'],
            'SO' => ['Sokoto', 'Tambuwal'],
            'ZM' => ['Gusau', 'Kaura Namoda'],
            'KE' => ['Birnin Kebbi', 'Jega'],
            'NI' => ['Minna', 'Suleja', 'Bida'],
            'KW' => ['Ilorin', 'Offa'],
            'KO' => ['Lokoja', 'Okene'],
            'NA' => ['Keffi', 'Akwanga', 'Lafia'],
            'GO' => ['Gombe', 'Kaltungo'],
            'AD' => ['Yola', 'Mubi'],
            'TA' => ['Jalingo', 'Wukari'],
            'BE' => ['Makurdi', 'Otukpo'],
            'OS' => ['Osogbo', 'Ile-Ife'],
            'ON' => ['Akure', 'Owo'],
            'EK' => ['Ado-Ekiti', 'Ikere'],
            'EB' => ['Abakaliki', 'Afikpo'],
            'AB' => ['Umuahia', 'Aba'],
            'BY' => ['Yenagoa', 'Amassoma'],
            'OG' => ['Abeokuta', 'Ijebu Ode'],
            'ND' => ['Yenagoa'],
        ],
        // United States Cities (Major cities per state)
        'US' => [
            'CA' => ['Los Angeles', 'San Francisco', 'San Diego', 'Sacramento', 'Fresno'],
            'TX' => ['Houston', 'Dallas', 'Austin', 'San Antonio', 'Fort Worth'],
            'FL' => ['Miami', 'Orlando', 'Tampa', 'Jacksonville', 'Tallahassee'],
            'NY' => ['New York City', 'Albany', 'Buffalo', 'Rochester', 'Syracuse'],
            'IL' => ['Chicago', 'Aurora', 'Naperville', 'Rockford'],
            'PA' => ['Philadelphia', 'Pittsburgh', 'Allentown', 'Erie'],
            'OH' => ['Columbus', 'Cleveland', 'Cincinnati', 'Toledo'],
            'GA' => ['Atlanta', 'Augusta', 'Columbus', 'Savannah'],
            'NC' => ['Charlotte', 'Raleigh', 'Greensboro', 'Durham'],
            'MI' => ['Detroit', 'Grand Rapids', 'Lansing', 'Ann Arbor'],
            'NJ' => ['Newark', 'Jersey City', 'Paterson', 'Elizabeth'],
            'VA' => ['Virginia Beach', 'Norfolk', 'Richmond', 'Arlington'],
            'WA' => ['Seattle', 'Spokane', 'Tacoma', 'Vancouver'],
            'MA' => ['Boston', 'Worcester', 'Springfield', 'Cambridge'],
            'AZ' => ['Phoenix', 'Tucson', 'Mesa', 'Chandler'],
            'IN' => ['Indianapolis', 'Fort Wayne', 'Evansville'],
            'TN' => ['Nashville', 'Memphis', 'Knoxville', 'Chattanooga'],
            'MO' => ['Kansas City', 'St. Louis', 'Springfield'],
            'MD' => ['Baltimore', 'Columbia', 'Silver Spring'],
            'WI' => ['Milwaukee', 'Madison', 'Green Bay'],
            'MN' => ['Minneapolis', 'Saint Paul', 'Rochester'],
            'CO' => ['Denver', 'Colorado Springs', 'Aurora'],
            'AL' => ['Birmingham', 'Montgomery', 'Mobile'],
            'SC' => ['Columbia', 'Charleston', 'North Charleston'],
            'LA' => ['New Orleans', 'Baton Rouge', 'Shreveport'],
            'KY' => ['Louisville', 'Lexington', 'Bowling Green'],
            'OR' => ['Portland', 'Eugene', 'Salem'],
            'OK' => ['Oklahoma City', 'Tulsa', 'Norman'],
            'CT' => ['Bridgeport', 'New Haven', 'Hartford'],
            'IA' => ['Des Moines', 'Cedar Rapids', 'Davenport'],
            'MS' => ['Jackson', 'Gulfport', 'Southaven'],
            'AR' => ['Little Rock', 'Fort Smith', 'Fayetteville'],
            'KS' => ['Wichita', 'Overland Park', 'Kansas City'],
            'UT' => ['Salt Lake City', 'Provo', 'West Valley City'],
            'NV' => ['Las Vegas', 'Henderson', 'Reno'],
            'NM' => ['Albuquerque', 'Las Cruces', 'Santa Fe'],
            'NE' => ['Omaha', 'Lincoln', 'Bellevue'],
            'WV' => ['Charleston', 'Huntington', 'Parkersburg'],
            'ID' => ['Boise', 'Meridian', 'Nampa'],
            'HI' => ['Honolulu', 'Pearl City', 'Hilo'],
            'NH' => ['Manchester', 'Nashua', 'Concord'],
            'ME' => ['Portland', 'Lewiston', 'Bangor'],
            'MT' => ['Billings', 'Missoula', 'Great Falls'],
            'WY' => ['Cheyenne', 'Casper', 'Laramie'],
            'ND' => ['Fargo', 'Bismarck', 'Grand Forks'],
            'SD' => ['Sioux Falls', 'Rapid City', 'Aberdeen'],
            'AK' => ['Anchorage', 'Fairbanks', 'Juneau'],
            'VT' => ['Burlington', 'South Burlington', 'Rutland'],
            'RI' => ['Providence', 'Warwick', 'Cranston'],
            'DE' => ['Wilmington', 'Dover', 'Newark'],
        ],
        // United Kingdom Cities
        'GB' => [
            'ENG' => ['London', 'Manchester', 'Birmingham', 'Liverpool', 'Leeds', 'Sheffield', 'Bristol', 'Newcastle'],
            'SCT' => ['Glasgow', 'Edinburgh', 'Aberdeen', 'Dundee'],
            'WLS' => ['Cardiff', 'Swansea', 'Newport', 'Wrexham'],
            'NIR' => ['Belfast', 'Derry', 'Lisburn'],
        ],
    ];

    return $cities[$countryCode][$provinceCode] ?? [];
}

/**
 * Get province/state label for a country
 *
 * @param string $countryCode Country code
 * @return string Label (e.g., "Province", "State")
 */
function getLocationProvinceLabel(string $countryCode): string {
    $labels = [
        'ZA' => 'Province',
        'NG' => 'State',
        'US' => 'State',
        'GB' => 'Region',
    ];
    return $labels[$countryCode] ?? 'Province/State';
}

/**
 * Output location data as JSON for JavaScript
 *
 * @param string $countryCode Optional country filter
 * @return never
 */
function outputLocationJson(string $countryCode = ''): never {
    header('Content-Type: application/json');
    header('Cache-Control: public, max-age=3600');

    $data = [
        'countries' => getLocationCountries(),
    ];

    if ($countryCode) {
        $data['provinces'] = getLocationProvinces($countryCode);
    } else {
        $data['provinces'] = [];
        foreach (array_column(getLocationCountries(), 'code') as $code) {
            $data['provinces'][$code] = getLocationProvinces($code);
        }
    }

    // Add cities for all provinces
    $data['cities'] = [];
    foreach (array_column(getLocationCountries(), 'code') as $code) {
        $data['cities'][$code] = [];
        foreach (getLocationProvinces($code) as $province) {
            $data['cities'][$code][$province['code']] = getLocationCities($code, $province['code']);
        }
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Handle AJAX requests for JSON data
if (isset($_GET['action']) && $_GET['action'] === 'json') {
    require_once __DIR__ . '/bootstrap.php';
    $country = $_GET['country'] ?? '';
    outputLocationJson($country);
}
