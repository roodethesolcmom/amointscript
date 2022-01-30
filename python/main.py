import requests
import json
from code import code

name = 'krafti'
client_id = '508c6123-d88d-4452-a7bb-df7829dab499'
client_secret = '4TnmQPFY7hiOoWdV9CWGosSXIUE7Ut01eQxSuKbHW3GhWEIC9JCq6jFkk1a5KVME'
redirect_uri = 'https://krafti.ru'

url = f'https://{name}.amocrm.ru/oauth2/access_token'

req = {
  "client_id": client_id,
  "client_secret": client_secret,
  "grant_type": 'authorization_code',
  "code": code,
  "redirect_uri": redirect_uri
}

reqq = {
  "client_id": client_id,
  "client_secret": client_secret,
  "grant_type": 'refresh_token',
  "refresh_token": code,
  "redirect_uri": redirect_uri
}

res = requests.post(url, req)

with open('keys.json', 'w') as file:
  file.write(res.text)
  # r_txt = res.text
  #  file.writelines('Access Token:\n')
  #  file.writelines(json.loads(r_txt)['access_token'])
  #  file.writelines('\n\nRefresh Token:\n')
  #  file.writelines(json.loads(r_txt)['refresh_token'])
with open('keys.json', 'r') as file:
  txt = file.read()

print(req)
print(res)
print(res.text)