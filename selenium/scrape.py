from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.firefox.options import Options
import time
import PowerBIPassword as pw

# Set up Firefox options
firefox_options = Options()
firefox_options.headless = True  # Set to False if you want to see the browser window

# Initialize the WebDriver for Firefox
driver = webdriver.Firefox(service=Service('/usr/local/bin/geckodriver'), options=firefox_options)

# Define the URL and credentials
login_url = 'https://dominos-pizza.login.go.sohacloud.net/#/login'
file_url = 'https://bireports.dominos.com.au/Reports/report/Netherlands/Store%20Reporting/Daily%20Store%20Report'

def login():
    # Open the login page
    driver.get(login_url)
    time.sleep(5)  # Wait for the page to load

    # Locate and fill in the username and password fields
    driver.find_element(By.ID, "username").send_keys(pw.username)
    time.sleep(1)
    driver.find_element(By.ID, "password").send_keys(pw.password)
    time.sleep(1)
    
    ## Submit the form (using Enter or by clicking the login button)
    driver.find_element(By.ID, 'cal-login-button').click()
    time.sleep(10)  # Wait for login to complete

def download_file():
    driver.get(file_url)
    time.sleep(10)

    #select country:
    driver.find_element(By.ID, 'ReportViewerControl_ctl04_ctl03_ddValue').click()
    time.sleep(1)


try:
    login()
    download_file()

finally:
    # Close the driver after the process
    driver.quit()



