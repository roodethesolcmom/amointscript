import requests
import json
from code import code

name = 'krafti'
client_id = '###'
client_secret = '###'
redirect_uri = 'https:/###.ru'

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
with open('keys.json', 'r') as file:
  txt = file.read()
