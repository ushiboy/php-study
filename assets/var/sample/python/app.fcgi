#!/usr/bin/env python3
# -*- coding: UTF-8 -*-

#https://docs.python.org/3/howto/webservers.html

import sys, os
from flup.server.fcgi import WSGIServer

count = 0

def app(environ, start_response):
    global count
    start_response('200 OK', [('Content-Type', 'text/plain')])
    count = count + 1
    return ['Hello World!\n', 'Count: %s\n' % count]

WSGIServer(app, bindAddress='/tmp/fcgi.sock').run()
