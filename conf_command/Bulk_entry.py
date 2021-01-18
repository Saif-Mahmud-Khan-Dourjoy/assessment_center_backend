import pandas as pd
import numpy as np
import json
import requests

# *********** ADMIN INFO ****************
USERNAME = "Admin" 
PASSWORD = "123456789"
EMAIL = "admin@nsl.com"
# ---------------------------------------
# ********** URL'S ********************
BASE_URL = "http://0.0.0.0:8000/api/v1/"
ADD_STUDENT = "students"
LOGIN = "login"

# --------------------------------


def login(username = USERNAME, password = PASSWORD, email=EMAIL):
    global BASE_URL
    try:
        url = BASE_URL+LOGIN
        payload={
            'username':username,
            'email':email,
            'password':password
        }
        headers = {}
        response = requests.request("POST",url, headers=headers, data=payload)
        print(type(response))
        print(response.text.user)
        return response
    except Exception as e:
        print('Error: Unable to login, coz: {}'.format(e))
        return json.dumps(str(e))

def addStudent():
    try:
        return True 
    except Exception as e:
        return json.dumps('Unable to add student, {}'.format(e))


def main():
    try :


        return 1
    except Exception as e:
        return json.dumps('Error: {}'.format(e))

if __name__ == "__main__":
    login()
