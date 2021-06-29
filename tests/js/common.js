
function md5File(file, callback) {
    var fileReader = new FileReader(),
        // box=document.getElementById('box');
        blobSlice = File.prototype.mozSlice || File.prototype.webkitSlice || File.prototype.slice,
        // file = document.getElementById("file").files[0],
        chunkSize = 2097152,
        // read in chunks of 2MB
        chunks = Math.ceil(file.size / chunkSize),
        currentChunk = 0,
        spark = new SparkMD5();

    fileReader.onload = function (e) {
        console.log("read chunk md5 ", currentChunk + 1, "of", chunks);
        spark.appendBinary(e.target.result); // append binary string
        currentChunk++;

        if (currentChunk < chunks) {
            loadNext();
        }
        else {
            console.log("finished loading");
            // box.innerText='MD5 hash:'+spark.end();
            var MD5Hash = spark.end();
            console.info("computed hash", MD5Hash); // compute hash
            callback(MD5Hash)
        }
    };

    function loadNext() {
        var start = currentChunk * chunkSize,
            end = start + chunkSize >= file.size ? file.size : start + chunkSize;

        fileReader.readAsBinaryString(blobSlice.call(file, start, end));
    };

    loadNext();
}

/*
 * sha1File v1.0.1
 * https://github.com/dwsVad/sha1File
 * (c) 2014 by Protsenko Vadim. All rights reserved.
 * https://github.com/dwsVad/sha1File/blob/master/LICENSE
*/
function sha1File(settings, callback) {
    var hash = [1732584193, -271733879, -1732584194, 271733878, -1009589776];
    var buffer = 1024 * 16 * 64;
    var currentChunk = 0;
    // read in chunks of 2MB
    var chunks = Math.ceil(settings.size / buffer);
    var sha1 = function (block, hash) {
        var words = [];
        var count_parts = 16;
        var h0 = hash[0],
            h1 = hash[1],
            h2 = hash[2],
            h3 = hash[3],
            h4 = hash[4];
        for (var i = 0; i < block.length; i += count_parts) {
            var th0 = h0,
                th1 = h1,
                th2 = h2,
                th3 = h3,
                th4 = h4;
            for (var j = 0; j < 80; j++) {
                if (j < count_parts)
                    words[j] = block[i + j] | 0;
                else {
                    var n = words[j - 3] ^ words[j - 8] ^ words[j - 14] ^ words[j - count_parts];
                    words[j] = (n << 1) | (n >>> 31);
                }
                var f, k;
                if (j < 20) {
                    f = (h1 & h2 | ~h1 & h3);
                    k = 1518500249;
                }
                else if (j < 40) {
                    f = (h1 ^ h2 ^ h3);
                    k = 1859775393;
                }
                else if (j < 60) {
                    f = (h1 & h2 | h1 & h3 | h2 & h3);
                    k = -1894007588;
                }
                else {
                    f = (h1 ^ h2 ^ h3);
                    k = -899497514;
                }

                var t = ((h0 << 5) | (h0 >>> 27)) + h4 + (words[j] >>> 0) + f + k;
                h4 = h3;
                h3 = h2;
                h2 = (h1 << 30) | (h1 >>> 2);
                h1 = h0;
                h0 = t;
            }
            h0 = (h0 + th0) | 0;
            h1 = (h1 + th1) | 0;
            h2 = (h2 + th2) | 0;
            h3 = (h3 + th3) | 0;
            h4 = (h4 + th4) | 0;
        }
        return [h0, h1, h2, h3, h4];
    }

    var run = function (file, inStart, inEnd) {
        var end = Math.min(inEnd, file.size);
        var start = inStart;
        var reader = new FileReader();

        reader.onload = function () {
            console.log("read chunk sha1 ", currentChunk + 1, "of", chunks);
            file.sha1_progress = (end * 100 / file.size);
            var event = event || window.event;
            var result = event.result || event.target.result
            var block = Crypto.util.bytesToWords(new Uint8Array(result));

            if (end === file.size) {
                var bTotal, bLeft, bTotalH, bTotalL;
                bTotal = file.size * 8;
                bLeft = (end - start) * 8;

                bTotalH = Math.floor(bTotal / 0x100000000);
                bTotalL = bTotal & 0xFFFFFFFF;

                // Padding
                block[bLeft >>> 5] |= 0x80 << (24 - bLeft % 32);
                block[((bLeft + 64 >>> 9) << 4) + 14] = bTotalH;
                block[((bLeft + 64 >>> 9) << 4) + 15] = bTotalL;

                hash = sha1(block, hash);
                file.sha1_hash = Crypto.util.bytesToHex(Crypto.util.wordsToBytes(hash));
                console.log(file.sha1_hash)
                currentChunk++;
                callback(file.sha1_hash)
            }
            else {
                hash = sha1(block, hash);
                start += buffer;
                end += buffer;
                currentChunk++;
                run(file, start, end);
            }
        }
        var blob = file.slice(start, end);
        reader.readAsArrayBuffer(blob);
    }

    var checkApi = function () {
        if ((typeof File == 'undefined'))
            return false;

        if (!File.prototype.slice) {
            if (File.prototype.webkitSlice)
                File.prototype.slice = File.prototype.webkitSlice;
            else if (File.prototype.mozSlice)
                File.prototype.slice = File.prototype.mozSlice;
        }

        if (!window.File || !window.FileReader || !window.FileList || !window.Blob || !File.prototype.slice)
            return false;

        return true;
    }

    if (checkApi()) {
        run(settings, 0, buffer);
    }
    else
        return false;
}