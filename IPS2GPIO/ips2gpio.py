#!/usr/bin/env python

import pigpio
import sys
import requests
import json
from requests.auth import HTTPBasicAuth


#[1]=RaspberryPi-Name
#[2]=RaspberryPi-Port default 8888
#[3]=Fernzugriff-User
#[4]=Fernzugriff-Passwort
#[5]=Variablen-ID
#[6]=Kommando
#[7-n]=Parameterwerte

pi = pigpio.pi(sys.argv[1])
port = int(sys.argv[2])
varid = int(sys.argv[5])
command = sys.argv[6]


# JSON-RPS zu IPS definieren
def IpsRpc(methodIps, paramIps):
    url = "http://127.0.0.1:3777/api/"
    auth=HTTPBasicAuth('paeper@horburg.de', 'Dennis1999')
    headers = {'content-type': 'application/json'}

    payload = {"method": methodIps, "params": paramIps, "jsonrpc": "2.0", "id": "0"}
    response = requests.post(url, auth=auth, data=json.dumps(payload), headers=headers)

if command == "set_mode":
                if sys.argv[8] == "IN":
                        pi.set_mode(int(sys.argv[7]), pigpio.INPUT)
                elif sys.argv[8] == "OUT":
                        pi.set_mode(int(sys.argv[7]), pigpio.OUTPUT)

if command == "set_PWM_dutycycle":
                pi.set_PWM_dutycycle(int(sys.argv[7]), int(sys.argv[8]))

if command == "set_PWM_dutycycle_RGB":
                pi.set_PWM_dutycycle(int(sys.argv[7]), int(sys.argv[8]))
                pi.set_PWM_dutycycle(int(sys.argv[9]), int(sys.argv[10]))
                pi.set_PWM_dutycycle(int(sys.argv[11]), int(sys.argv[12]))

if command == "write":
                pi.write(int(sys.argv[7]), int(sys.argv[8]))

IpsRpc("SetValue", [varid,command])

pi.stop()

