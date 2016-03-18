# andryou twitter cleaner
A simple, open-source (free), and database-less Twitter cleaner that allows people to quickly delete their Likes and/or Tweets on Twitter.

Lovingly coded in PHP, uses some native JavaScript and a sprinkling of CSS. Uses tmhOAuth to authenticate with Twitter.
It's not the prettiest thing I've created, but I can tell you it works quite well.

This is the only open-source alternative to the likes of www.tweetdelete.net, www.tweetdeleter.com, www.tweeteraser.com, www.twitwipe.com, and others that I know of, which is why I'm deciding to make my code open and free.
I created this because I didn't feel comfortable authorizing sites (who haven't published their code) to have access to all my Twitter data.

Note: due to the nature of this script (irreversible deletion), tread accordingly.

## Features
* delete everything you've Liked/Favourited
* delete everything you've Tweeted and Retweeted (automatically tries to delete as close to Twitter's limit as it can: 3,200)
* some intelligence coded in to play nicely with Twitter's rate limits
* uses PHP $_SESSION instead of a database when handling user tokens because there is no need to store/log data
* code is fully available for scrutiny/review

## Live Site
https://www.andryou.com/twittercleaner

## License
* You are free to do whatever you want with the code
* All I ask/request is that you leave the footer as-is and/or leave a link to my Twitter account somewhere in the page :)

## Showing Thanks
* A mention/thanks on Twitter (to @andryou - https://twitter.com/andryou) would be much appreciated!
* If you found this tool useful or if you wanted to show more appreciation, you are able to donate some money to me <3 A donation button can be found on https://www.andryou.com/twittercleaner