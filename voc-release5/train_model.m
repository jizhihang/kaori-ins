function train_model(cls, n, note, dotrainval, testyear)
% Train and evaluate a model. 
%   pascal(cls, n, note, dotrainval, testyear)
%
%   The model will be a mixture of n star models, each of which
%   has 2 latent orientations.
%
% Arguments
%   cls           Object class to train and evaluate
%   n             Number of aspect ratio clusters to use
%                 (The final model has 2*n components)
%   note          Save a note in the model.note field that describes this model
%   dotrainval    Also evaluate on the trainval dataset
%                 This is used to collect training data for context rescoring
%   testyear      Test set year (e.g., '2007', '2011')

% //DuyCmt//: train_model tuong tu nhu pascal.m, chi khac la chi su dung phan train (bo phan test/evaluate)

startup;

conf = voc_config();
cachedir = conf.paths.model_dir;
testset = conf.eval.test_set;

% TODO: should save entire code used for this run
% Take the code, zip it into an archive named by date
% print the name of the code archive to the log file
% add the code name to the training note
timestamp = datestr(datevec(now()), 'dd.mmm.yyyy:HH.MM.SS');

% Set the note to the training time if none is given
if nargin < 3
  note = timestamp;
end

% Don't evaluate trainval by default
if nargin < 4
  dotrainval = false;
end

if nargin < 5
  % which year to test on -- a string, e.g., '2007'.
  testyear = conf.pascal.year;
end

% Record a log of the training and test procedure
diary(conf.training.log([cls '-' timestamp]));

% Train a model (and record how long it took)
th = tic;
try
	model = pascal_train(cls, n, note);
catch 
	quit;
end
toc(th);

% Free the feature vector cache memory
fv_cache('free');

quit;