# udemy-snappy-list-builder

Udemy Snappy List Builder requires the Custom Post Type UI plugin which was used to create custom list and subscriber post types. 

The plugin allows those with admin priviledges to add new subscribers to the Subscriber and List custome post types in order to add subscribers and create subscriber lists using names and emails. This info is stored in the database. 
Subscribers can be added as long as their emails are not already recorded on the system in which case updates can be made to the subscriber but they cannot be added as new again unless they have been removed from the database. 

Problems:
Should allow subscribers to be added on a web page rather than only by admin.
There are some issues around validation for subscriber entries into the database where the "Post published" notice still pops up to alert to the fact that duplicated email address entries were not successfully added to the database. 
It also currently still adds an empty entry into the database when atempting to add non unique email address subscribers. 
