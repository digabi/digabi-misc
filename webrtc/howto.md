WebRTC PoC, on Debian wheezy
===================================
Based on [WebRTC Getting 
Started](http://www.webrtc.org/reference/getting-started).

## Prerequisites
Install support for git and subversion:

    apt-get install git subversion

[Install depot_tools](https://sites.google.com/a/chromium.org/dev/developers/how-tos/depottools):

    cd $HOME
    git clone https://chromium.googlesource.com/chromium/tools/depot_tools.git
    echo "export PATH=\"$HOME/depot_tools:$PATH\"" >~/.bashrc
    source ~/.bashrc

Install other essential packages:

    apt-get install build-essential libnss3-dev libasound2-dev libpulse-dev libjpeg62-dev libxv-dev libgtk2.0-dev libexpat1-dev gcj-4.7-jdk


## Fetch code

    mkdir webrtc-src ; cd webrtc-src
    gclient config http://webrtc.googlecode.com/svn/trunk
    
    # On Debian, gclient / gyp doesn't find include/jni.h without this (from package gcj-4.7-jdk)
    export JAVA_HOME="/usr/lib/gcc/x86_64-linux-gnu/4.7/"

    gclient sync --force 


## Build

    cd trunk
    ninja -C out/Debug peerconnection_server
    ninja -C out/Debug peerconnection_client


## Run
Server (by default, runs at port 8888).

    ./out/Debug/peerconnection_server

Client

    ./out/Debug/peerconnection_client
