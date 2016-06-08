#!/usr/bin/env python

import pigpio
import sys
import requests
import json
from requests.auth import HTTPBasicAuth


#[1]=RaspberryPi-Name
#[2]=RaspberryPi-Port default 8888
#[3]=IPS-Server-IP
#[4]=JSON-RPC-Port
#[5]=Fernzugriff-User
#[6]=Fernzugriff-Passwort
#[7]=Variablen-ID
#[8]=Kommando
#[9-n]=Parameterwerte

pi = pigpio.pi(sys.argv[1])
port = sys.argv[2]
command = sys.argv[8]


# JSON-RPS zu IPS definieren
def IpsRpc(methodIps, paramIps):
    url = "http://192.168.178.47:3777/api/"
    auth=HTTPBasicAuth('paeper@horburg.de', 'Dennis1999')
    headers = {'content-type': 'application/json'}

    payload = {"method": methodIps, "params": paramIps, "jsonrpc": "2.0", "id": "0"}
    response = requests.post(url, auth=auth, data=json.dumps(payload), headers=headers)

if command == "set_mode":

                if sys.argv[10] == "IN":
                        pi.set_mode(int(sys.argv[9]), pigpio.INPUT)
                elif sys.argv[10] == "OUT":
                        pi.set_mode(int(sys.argv[9]), pigpio.OUTPUT)

if command == "set_PWM_dutycycle":
        for g in range(3,3+(len(sys.argv)-4)/2):
                pin = int(sys.argv[g*2-2])
                value = int(sys.argv[g*2-1])
                pi.set_PWM_dutycycle(pin, value)

variable=29419
IpsRpc("SetValue", [variable,command])

pi.stop()

