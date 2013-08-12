function res = countLines (fileName)
if (exist(fileName) == 0)
    res = 0
else
    fid = fopen(fileName, 'rb');
    fseek(fid, 0, 'eof');
    fileSize = ftell(fid);
    frewind(fid);
    data = fread(fid, fileSize, 'uint8');
    res = sum(data == 10);
    disp(res);
    fclose(fid);
end
end