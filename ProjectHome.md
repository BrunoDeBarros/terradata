## What is Terra Data? ##

Terra Data combines all the tasks we often repeatedly perform that are related to our data and gives them to you in an extremely easy to use library. From validation to HTML form generation, and even API generation, and even exporting/importing data to/from different formats, everything is taken care of for you. All you need to do is create a simple PHP array that tells Terra Data what the data you want to handle is.

## Features ##

#### Create, read, update and delete records ####
As is expected from modern database abstraction layers. It only works with MySQL at the moment, I am planning on using the Adapter design pattern to make it work perfectly with other databases.

#### Validate, sanitize and escape data ####
Make sure your data is valid and won't break your website, and mainpulate it so that it works flawlessly with your system.

#### Analyze data ####
Be it simply counting all the rows in a set of data, finding out an average, or performing more advanced statistical analysis, you can easily do it with Terra Data.

#### Import/Export data ####
Convert data between different formats and import or export that data into different formats. For example, you can use getLatest(10)->export(‘RSS’) and get an RSS file containing the 10 latest results in a data set (getLatest would know which field the creation date field is, and act accordingly). JSON, CSV and XLS are three more data formats that will be supported in the near future.

#### Generate the HTML ####
Have Terra Data generate the HTML forms for you. It will manipulate the data, repopulate the form, display validation errors and insert default values for you, automatically. So there is nothing for you to do. And the best part is that it's easy to integrate it into any existing layout you might have, and if you wish to, you can edit the form templates. It couldn't be easier.

#### Handle Data Relationships ####
For example, if you have an instance of this library that has all the information about your users table, you are not only able to handle the data on that table, but also able to get all the posts related to that user, or some additional user metadata that’s in another table. This is possible at the moment using custom fields (more below).

#### Custom Fields ####
Sometimes, you don’t just want to stick with the fields in your database table, you want more. For example, you might want to display a list of your users, and the number of purchases they have completed on your website. Or you might wanna get an array of IDs of posts that your user has written. With custom fields, you can – you tell the library where the data is (for example, where a list of all the purchases is, so that it can count how many purchases a customer has made), and it will take care of everything for you.

#### Field Identifiers ####
Field identifiers allow you to completely abstract your code from the underlying database structure. For example, let’s say you have two database tables, one with USERNAME and PASSWORD and the other with user\_name and user\_password. Or you had USERNAME and PASSWORD, but decided to change them to something else. By using field identifiers, it is possible for you to change the database fields, and the only thing you have to update is the configuration of your data set. The code remains the same.

#### Method Query Language (MQL) ####
While developing this library, I felt that I needed a way to make my querying even easier. I had found myself writing functions getById, getByUser, and others, over the years, and I decided to implement this into the library. The results is an astonishingly simple way to both query and update the database: getFieldsByFields() and setFieldsByFields(). An example would be getUsernameById($Id), to get a user’s username, given their ID, or setAddressAndZipcodeById($Id, $Address, $Zipcode) to update the address and zipcode of the user with a given ID. It’s that simple. And thanks to Field Identifiers, if your database structure changes, you still won’t have to change the function names, so it won’t be a problem.

#### REST-ful API ####
Just about every modern day website has to have an API to enable remote access to their data. The library includes an api/ folder, with everything necessary to make remote calls to data using method query language. By default, you can use: http://www.example.com/api/api-key/data-set/method-query/arguments. An example of this is http://api.example.com/api-key/articles/getLatest/3. This API can easily be customized to your needs, and by default it is impossible to access or use any field in the API, you have to enable API access for them in the configuration of your data set. For example, you might want to allow API access to a list of your posts, their author’s, post creation date, and all of that, but not their metadata.