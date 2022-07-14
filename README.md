# Welcome to Softnauts!  

## Your task:

Implement an API endpoint `POST /api/gold`.
Any external client should be able to call this endpoint, to get **average** gold price in Poland, for any given date range.

To get real gold prices, use NBP service at http://api.nbp.pl/ (this link will open API doc). Yes, we will check if return values are real.
You may use prepared App\Controller\GoldController if You want.

### API request
POST request format should be 
```
{
    "from": "2021-01-01T00:00:00Z", 
    "to": "2021-01-31T00:00:00Z"
}
```

- "**from**" and "**to**" fields are strings, with date and time in ISO 8601 format (JavaScript compatible).
- You probably won't need time component, but regardless, input requirement is still full ISO8601 timestamp. All other date formats should fail validation, and throw relevant error message. The same goes for missing fields or other validation errors. 
- POST method for getting data may seem strange, but let's just assume that it makes sense from global perspective.

### API response 
It should be a JSON, with format:
```
{ 
    "from": "2021-01-04T00:00:00+02:00", 
    "to": "2021-01-29T00:00:00+02:00", 
    "avg": 12345.67 
}
```

- In the example above, **"from"** is not equal to requested **"from"**! That's because NBP don't track gold prices at weekends. Respond with real data!
- Response timezone should always be current Polish timezone (Europe/Warsaw). At the time of writing, this is +02:00.
- **avg** field type is _float_, but keep in mind that it means _money_, not just any float.

### Unit testing
Fix tests in `tests/GoldControllerTest.php`. You may want to prepare few additional test cases, if necessary.

### Additional constraints:
1. What we will look at:
    1. If code compiles and returns correct values,
    2. Code structure, cleanliness and Your design decisions (SOLID, design patterns, and/or conformance to Symfony guidelines).
    3. API tests / Unit tests.
2. Bonus points for:
    1. Caching NBP responses, and serving data from cache. Historical gold prices won't change over time :)
    2. Logging debug messages
3. You can add any dependency You want, create services, entities, remove our code, anything.
    1. Of course, these modifications should be sane. 
    2. But if You do something "strange" and successfully defend it at the meeting - a big plus!
4. You _have_ to use **PHP >= 8.1** and Composer 2
5. Please, write in English! (variable names, comments, commit messages - all of it)

### Installation:
1. `git clone git@bitbucket.org:softnauts/php-gold-prices.git` 
2. `composer install`
3. Copy `.env` -> `.env.local` and fill database credentials
4. `./bin/console doctrine:database:drop --if-exists --force`
4. `./bin/console doctrine:database:create --no-interaction`
4. `./bin/console doctrine:migrations:migrate --no-interaction`

## Submitting an assignment

1. If You have any thoughts, comments, future recommendations -  delete contents of this README.md file and put them here.
2. Push source code into Your own public repository (GitHub, BitBucket, whatever), and send us the link.


