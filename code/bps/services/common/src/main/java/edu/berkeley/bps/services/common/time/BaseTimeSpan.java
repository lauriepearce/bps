package edu.berkeley.bps.services.common.time;

public abstract class BaseTimeSpan implements TimeSpan {
	private static final String myClass = "BaseTimeSpan";

	protected double halfWindow = 0;
	protected double stdDev = 0;
	private double twoVariance = 0;

	public BaseTimeSpan(double stdDev, double window) {
		super();
		setStdDev(stdDev);
		setWindow(window);
	}

	/**
	 * @return the window
	 */
	public double getWindow() {
		return halfWindow*2;
	}

	/**
	 * @param window the window to set
	 */
	public void setWindow(double window) {
		if(window<0)
			throw new IllegalArgumentException(myClass+" Window must be >= 0");
		this.halfWindow = window/2;
	}

	public double getStdDev() {
		return stdDev;
	}

	public void setStdDev(double value) {
		if(value<=0)
			throw new IllegalArgumentException(myClass+" StdDev must be > 0");
		stdDev = value;
		twoVariance = 2*stdDev*stdDev;
	}
	
	public long getClosestWindowPointToTime(long time) {
		long center = getCenterPoint();
		long earliest = (long)(center-halfWindow);
		long latest = (long)(center-halfWindow);
		if(time>earliest && time < latest)
			return time;
		return (time<center)?earliest:latest;
		
	}

	/**
	 * Computes the likelihood that the passed time is within this TimeSpan
	 * Will return 1 if it is within the window, and will return a value
	 * between 0 and 1 otherwise.
	 * @param time the time to consider
	 * @return likelihood in the range of 0 to 1
	 */
	private double computeProbabilityForTime(long time) {
		// Get the distance from our center point.
		double delta = Math.abs(time-getCenterPoint());
		// Reduce that by our half-window, to see how close time is to our edge
		// If the time is within the window, force delta to 0;
		delta = Math.max(0, delta-halfWindow);
		// within window, probability is 1.0
		return (delta==0)?1.0 : Math.pow(Math.E, (-delta*delta/twoVariance));
	}

	public double computeMutualProbability(TimeSpan span) {
		long center = getCenterPoint();
		long earliest = (long)(center-halfWindow);
		long latest = (long)(center+halfWindow);
		long otherCenter = span.getCenterPoint();
		double otherHalfWindow = span.getWindow()/2;
		long otherEarliest = (long)(otherCenter-otherHalfWindow);
		long otherLatest = (long)(otherCenter+otherHalfWindow);
		if(otherLatest<earliest) {
			// Earlier span - use otherLatest in computation
			return computeProbabilityForTime(otherLatest);
		} else  if(otherEarliest>latest) {
			// Later span - use otherEarliest in computation
			return computeProbabilityForTime(otherEarliest);
		} else {
			// overlapping timespan - return likelihood 1
			return 1.0;
		}
	}
	
	public String getDisplayString() {
		return TimeUtils.millisToSimpleYearString(getCenterPoint())
				+" +/- "
				+TimeUtils.millisToYearOffsetString(halfWindow)
				+ " yrs";
	}
	
	public String toString() {
		return getClass().getName()+": " + getDisplayString();
	}

}
