# Dominos Hustle-Times Leaderboard site

This is a site that can show a leaderboard for hustle times for dominos drivers. It retrieves the leaderboard from a [SQL-database](#database-structure)
You can upload new times on the [generate.php page](#generate-page).

I started a few test to automate the generation of the leaderboard using selenium: jump to [automation](#automation-using-selenium)

## Database structure
You should create a file called 'database.php' to add the database login variables ($servername, $username, $password, $dbname).

The database consists of one table called 'drivers' and a table per week called 'week_\<wwyy>'

### drivers(<u>id</u>, name, points) <br>
id: 4-digit code used by PULSE to identify driver, **must correspond to the system.** <br>
name: name of the driver. Can be anything (/has no dependencies anywhere)<br>
points: total points scored so far by the driver.

### week_\<wwyy>(<u>id</u>, name, time, percentage, points_this_week, total_points)<br>
id: entry identifier. Not used.<br>
name: driver ID, 4-digit code used by PULSE to identify driver.<br>
time: avg hustle time this week.<br>
percentage: avg percentage of app usage this week.<br>
points_this_week: points aquired this week. Calculated during the upload (see [Generate page](#generate-page)).<br>
total_points: the total points this driver had during this week.<br>

## Generate page

### Uploading a new week leaderboard tutorial
<ol>
    <li>
        Open 'rapporten' on chrome. Navigate to 'Store Reporting' and then 'Daily Store Report'.
    </li>
    <li>
        Copy (select from bottom-right to top left entry, ctrl+c)
    </li>
    <li>
        Paste the two tables next to each other in a new Excel sheet. 
        Ensure that the columns are aligned properly.
    </li>
    <li>
        Your table should look something like this:
        <pre>Store ID | Driver ID | Monday | ... | Sunday | Week Avg | Store ID | Driver ID | Monday | ... | Sunday | Week Avg </pre>
    </li>
    <li>
        After ensuring that the data is structured correctly, save the Excel file.
    </li>
    <li>
        Convert the Excel file to a CSV file:
        <ul>
            <li>In Excel, go to <strong>File</strong> > <strong>Save As</strong>.</li>
            <li>Choose the location where you want to save the file.</li>
            <li>In the <strong>Save as type</strong> dropdown, select <strong>CSV (Comma delimited) (*.csv)</strong>.</li>
            <li>Click <strong>Save</strong>.</li>
            <li>If prompted about features not compatible with CSV format, click <strong>Yes</strong>.</li>
        </ul>
    </li>
    <li>
        Enter the week number and year below.
    </li>
    <li>
        Select the CSV file you just created by clicking <strong>browse</strong> below.
    </li>
    <li>
        Hit upload, points are calculated automatically.
    </li>
</ol>
<h4>Note:</h4>
<p>
    Ensure that all performance metrics are in numeric format. Entries with percentages below the threshold will not receive any points, so make sure to review the data accordingly.
</p>

### Driver list
Make sure all drivers that may show up in the reports are added to this list. You can do so with the form under Add Driver.
If you make a mistake you can remove it again.

### Reset points
This button resets all points in the [drivers table](#driversid-name-points).

# Automation using Selenium
***UNFINISHED***
The goal of this pythonscript is to retrieve the daily store report from the powerBI database, and extract the relevant hustle times and percentages to generate the leaderboard automatically.

You could set up a server that runs this script every week (e.g. as a cron job), so the leaderboard stays up to date.

Save the login-details for the PowerBI reports site in the "PowerBIPassword.py" file.


