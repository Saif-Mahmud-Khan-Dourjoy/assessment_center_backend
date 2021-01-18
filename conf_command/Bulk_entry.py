import pandas as pd
import numpy as np
import json
import requests
import time

# *********** ADMIN INFO ****************
USERNAME = "Admin" 
PASSWORD = "123456789"
EMAIL = "admin@nsl.com"

# ********** URL'S ********************
BASE_URL = "http://0.0.0.0:8000/api/v1/"
ADD_STUDENT = "students"
LOGIN = "login"
# ********** STUDENT STATIC INFO *******
STUDENT_INFO = 'student.csv'
STUDENT_ADD_URL = "students"
STUDENT_INSTIUTE_ID ='1'
STUEDNT_SKYPE = 'N/A'
STUDENT_PROFESSION= 'student'
STUDENT_ABOUT = 'N/A'
STUDENT_IMG = 'N/A'
STUDENT_ADDRESS= 'N/A'
STUDENT_ZIPCODE = '0'
STUDENT_COUNTRY = 'BANGLADESH'
STUDENT_ROLE_ID = '1'
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
        data = json.loads(response.text)
        # print('response-code: {}'.format(response.status_code))
        print(data)
        return data
    except Exception as e:
        print('Error: Unable to login, coz: {}'.format(e))
        return json.dumps(str(e))

def addStudent(institute_id, token):
    global BASE_URL
    global STUDENT_ADD_URL
    failed_students = pd.DataFrame(columns=['first_name', 'last_name', 'institution_name','email','phone','skype','profession','about','image','zipcode','country'])
    success_studens = pd.DataFrame(columns=['id','user_id','instiute_id','first_name','last_name', 'email','phone','skype','profession','skill','about','img','address','zipcode','country','guard_name','created_at','updated_at'])
    try:
        students = pd.read_csv(STUDENT_INFO)
        url = BASE_URL+STUDENT_ADD_URL
        for i in range(0,len(students)):
            student = students.iloc[i]
            payload = {
                'first_name':student['first_name'],
                'last_name':student['last_name'],
                'email' : student['email'],
                'phone':student['phone'],
                'skype':student['skype'] if student['skype'] else STUDENT_SKYPE,
                'profession': student['profession'] if student['profession'] else STUDENT_PROFESSION,
                'about':student['about'] if student['about'] else STUDENT_ABOUT,
                'img':student['image'] if student['image'] else STUDENT_IMG,
                'address':student['address'] if student['address'] else STUDENT_ADDRESS,
                'zipcode':student['zipcode'] if student['zipcode'] else STUDENT_ZIPCODE ,        
                'country':student['country'] if student['country'] else STUDENT_COUNTRY,
                'institute_id':STUDENT_INSTIUTE_ID
            }
            print(payload)
            headers={
                'Accept': 'application/json',
                'Authorization': 'Bearer '+token 
            }
            response = requests.request('POST',url, headers=headers, data=payload)
            response_data =json.loads(response.text)
            print('response-code: {}'.format(response.status_code))
            print('response: '.format(response_data))
            if int(response.status_code)!=200:
                print('before checking..')
                failed_students = failed_students.append(student, ignore_index=True)
                print('checking...')
                break
            print('response-data:{}'.format(response_data))
            success_studens = success_studens.append(dict(response_data['student']['user_profile']))

            break
        timestr = time.strftime("%Y%m%d-%H%M%S")
        failed_students.to_csv('history/'+timestr+'failed.csv')
        success_studens.to_csv('history/'+timestr+'success.csv')
        return True 
    except Exception as e:
        print('Unable to add student, {}'.format(e))
        return json.dumps(str(e))

def main():
    try :
        info=login()
        addStudent(STUDENT_INSTIUTE_ID, info['token'])
        return 1
    except Exception as e:
        return json.dumps('Error: {}'.format(e))

if __name__ == "__main__":
    main()
    # login()
    # addStudent()
