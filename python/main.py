import requests
import json
from code import code

name = 'krafti'
client_id = 'f9bc4b58-6897-478b-8855-a6edb7509982'
client_secret = '06A2CelBiAPEEfOmpWWxoqYliOyouk5UXXlCTlInc1s3fJivc5J3GxtnGN4R7WZ7'
redirect_uri = 'https://krafti.ru'

url = f'https://{name}.amocrm.ru/oauth2/access_token'

req = {
  "client_id": client_id,
  "client_secret": client_secret,
  "grant_type": "authorization_code",
  "code": code,
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

print(txt)