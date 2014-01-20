#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""Convert hosts.csv to ISC DHCP server host entries

Usage:
 python hosts.py --infile hosts.csv --domain demo.digabi.fi
 
CSV headers (tested with):
 mac1,mac2,manufacturer,model,type,interface,description,name,additional details
 
"""

# (c) 2013, 2014 Ylioppilastutkintolautakunta
# Author: Ville Korhonen <ville.korhonen@ylioppilastutkinto.fi

import os
import sys
import argparse
import csv

def host(hostname, mac, address):
    if address is None:
        fixed = ''
    else:
        fixed = 'fixed-address %(address)s; ' % {'address': address,}

    if mac is None or mac == '':
        disabled = '# '
    else:
        disabled = ''

    return "%(disabled)shost %(hostname)s { hardware-ethernet %(mac)s; %(address)s}" % {
            'disabled': disabled,
            'hostname': hostname,
            'mac': mac,
            'address': fixed,
    }

def next_mac(mac):
    for i in range(len(mac) - 1, 0, -1):
        mac[i] += 1
        if mac[i] > 255:
            mac[i] = 0
        else:
            break
    return mac

def list_smaller_than(list1, list2):
    if len(list1) != len(list2):
        return None

    for i in range(0, len(list1)):
        if list1[i] > list2[i]:
            return False
    return True

def mac_range(first, last):
    macs = []
    mac1 = [int(x, 16) for x in first.split(':')]
    mac2 = [int(x, 16) for x in last.split(':')]

    for i in range(0, len(mac1)):
        if mac1[i] > mac2[i]:
            mac1, mac2 = mac2, mac1
            break

    while list_smaller_than(mac1, mac2):
        nmac = ":".join([hex(x)[2:].rjust(2,"0") for x in mac1])
        macs.append(nmac)
        mac1 = next_mac(mac1)
    return macs

def normalize_host(s):
    if s is not None and len(s) > 0:
        return s.strip().lower()
    return ""

def convert_to_records(data, domain):
    records = []

    _hostname = normalize_host(data['name'])
    _mac = normalize_host(data['mac1'])

    if _hostname == '':
        return []

    if data['mac2'] is None:
        if data['interface'] is None:
            hostname = '%s' % _hostname
        else:
            hostname = '%s-%s' % (
                                  _hostname,
                                  normalize_host(data['interface']),
                                  )

        address = '%s.%s' % (hostname, domain)
        records.append(host(hostname.ljust(20), _mac, address))
    else:
        _mac2 = normalize_host(data['mac2'])
        macs = mac_range(_mac, _mac2)

        for i in range(0, len(macs)):
            hostname = '%s-port%d' % (_hostname, i)
            address = '%s.%s' % (hostname, domain)
            records.append(host(hostname.ljust(20), macs[i], address))
    return records

def parse_csv(filename):
    if not os.path.exists(filename):
        return False
    data = []

    with open(filename, 'r') as csvfile:
        reader = csv.reader(csvfile, delimiter=',', quotechar='"')
        
        headers = []
        
        for row in reader:
            if len(headers) == 0:
                headers = row
                continue     
            
            tmp = {}
            for i in range(0, len(headers)):
                if row[i] == '':
                    row[i] = None
                tmp[headers[i]] = row[i]
            data.append(tmp)
    return data                

def main(args):
    data = parse_csv(args.infile)
    if data == False:
        print "File not found: %s, exiting..." % args.infile
        return 1

    records = []

    for row in data:
        records += convert_to_records(data=row, domain=args.domain)

    print "\n".join(records)
    
    return 0    

def run():
    parser = argparse.ArgumentParser()
    parser.add_argument('-i', '--infile', dest='infile')
    parser.add_argument('-d', '--domain', dest='domain')
    args = parser.parse_args()
    sys.exit(main(args))

if __name__ == "__main__":
    run()
