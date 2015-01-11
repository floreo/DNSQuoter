## WHAT'S THAT

It's just a PHP script to update a TXT record quotes in your zone through OVH API. It's totally useless :)

## REQUIREMENTS

Obviously PHP in CLI, and also php-curl.

## INSTALLATION

Use git to get a fresh copy of it:
    
    git clone https://github.com/floreo/DNSQuoter.git

Then, go in folder and do a copy of config.txt.orig in config.txt

You'll need to set the good accessRules to your zone at https://api.ovh.com/g934.first_step_with_api
Create your app there: https://eu.api.ovh.com/createApp/

Then execute the following command, replace first the value for X-Ovh-Application, < domain name > and < whatever you want > (it's juste where you'll be redirected once you're logged in, it doesn't matter here).

    curl -XPOST -H"X-Ovh-Application: xxxxxxxxxx" -H "Content-type: application/json" \
    https://eu.api.ovh.com/1.0/auth/credential  -d '{
    "accessRules": [
        {
            "method": "GET",
            "path": "/domain/zone/< domain name >/record"
        },
        {
            "method": "PUT",
            "path": "/domain/zone/< domain name >/record/*"
        },
        {
            "method": "POST",
            "path": "/domain/zone/< domain name >/refresh"
        }
    ],
    "redirection":"http://< whatever you want >/"
    }'

You'll get a validation link, browse it, done !

Now fill the config.txt file with the good values for each field.

Finally, write each of your quotes in the file quotes.txt, one per line.

Set a cron for your user

	* * * * * /usr/bin/php /path-to-folder/DNSQuoter/index.php

Voilà !