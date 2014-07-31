cd /net/per610a/export/das09f/satoh-lab/minhduc/resources/object_Detection/voc-release5
startup
for i = 9069:9098
	load(['/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/' num2str(i) '/query_' num2str(i) '_final.mat']);
	visualizemodel(model);
	pause;
end