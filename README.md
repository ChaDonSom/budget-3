<img src="https://budget.somero.dev/favicon.ico" >

# Somero Budget v3

## The idea:

<img src="https://s3.us-west-2.amazonaws.com/secure.notion-static.com/4a2c6b72-d5ad-4c0d-a7e6-6f71ea0a8d8c/Untitled.png?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Credential=AKIAT73L2G45EIPT3X45%2F20220510%2Fus-west-2%2Fs3%2Faws4_request&X-Amz-Date=20220510T011023Z&X-Amz-Expires=86400&X-Amz-Signature=4c8d78e9413bcd66ff51e52c13a45487aa6aaa7e3a256c9edfd1826ae6d80e77&X-Amz-SignedHeaders=host&response-content-disposition=filename%20%3D%22Untitled.png%22&x-id=GetObject">

---

The simplest way to budget: put however much you want into each account, and it waits until you've made all your adjustments to do away with the math. That way, you can assign however much you want to every 'account' you have, then see how much you have to take from your check (and how much you'll have left).

&nbsp;

## The underneath
The app theoretically has two main models:
- accounts
- audits

An account is simply a name with a total dollar amount associated with it. Every time we edit an account's total, we log an 'audit' recording the old and new value.

Every edit will have a date and batch-number associated with it. If its date is today, the edit will be performed synchronously, an audit made, and the result returned to the client. If its date is in the future, it will be scheduled. When the edit is made, an audit will be made.

&nbsp;

# Get started

```bash
cp .env.example .env

composer install
npm install
npm start
```

[View the Vue readme](./vue-readme.md)

[View the Laravel readme](./laravel-readme.md)

# Publish it

## Using Laravel Forge

**Update your nginx config:** provide two different `location` blocks: one for the frontend, one for the backend. Use explicitly-registered routes for your backend, for both legibility and safety (and because no other way works). These will replace the existing `location /` block.
```nginx
location / {
    # {{ ROOT_PATH }}/dist in a Forge Template
    root /home/user/domain.com/dist;
    try_files $uri $uri/ /index.html;
}

location ~ ^\/(api|sanctum|login|logout|register|user|_ignition|password-reset|clockwork|__clockwork).* {
    # {{ PATH }} in a Forge Template
    root /home/user/domain.com/public;
    try_files $uri $uri/ /index.php?$query_string;
}
```